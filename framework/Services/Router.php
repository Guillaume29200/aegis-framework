<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Service Router - Gestion des routes
 * 
 * Support:
 * - Routes GET/POST/PUT/DELETE
 * - Paramètres dynamiques ({id}, {slug})
 * - Middlewares (auth, csrf, rate limit)
 * - Groupes de routes
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private ?string $currentGroup = null;
    private array $dependencies = [];

    /** Garde CSRF globale (validation centralisée des requêtes mutantes) */
    private ?\Framework\Security\CSRFProtection $csrfGuard = null;
    /** Patterns d'URI exemptés de la garde CSRF (webhooks, API publiques) */
    private array $csrfExcept = [];
    
    /**
     * Définir les dépendances pour l'injection
     */
    public function setDependencies(array $dependencies): void
    {
        $this->dependencies = $dependencies;
    }
    
    /**
     * Ajouter une dépendance
     */
    public function addDependency(string $name, $instance): void
    {
        $this->dependencies[$name] = $instance;
    }

    /**
     * Activer la garde CSRF globale.
     *
     * Toute requête POST/PUT/PATCH/DELETE est automatiquement validée
     * (champ csrf_token ou header X-CSRF-Token) avant d'atteindre le handler.
     * Les contrôleurs n'ont plus besoin de valider manuellement (mais peuvent
     * le faire en défense en profondeur — le token n'est pas consommé).
     *
     * @param array $except Liste de patterns d'URI exemptés (wildcard * supporté)
     */
    public function enableCsrfGuard(\Framework\Security\CSRFProtection $csrf, array $except = []): void
    {
        $this->csrfGuard = $csrf;
        $this->csrfExcept = $except;
    }

    /**
     * Vérifier la garde CSRF pour la requête courante.
     * Lève une CSRFException (403) si le token est absent ou invalide.
     */
    private function enforceCsrf(string $method, string $uri): void
    {
        if ($this->csrfGuard === null) {
            return;
        }

        // Seules les méthodes mutantes sont protégées
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        // Routes exemptées (webhooks, API publiques)
        foreach ($this->csrfExcept as $pattern) {
            $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';
            if (preg_match($regex, $uri)) {
                return;
            }
        }

        // Lève CSRFException si token absent/invalide → page 403 (bootstrap)
        $this->csrfGuard->validateRequest('default');
    }
    
    /**
     * Enregistrer route GET
     */
    public function get(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }
    
    /**
     * Enregistrer route POST
     */
    public function post(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }
    
    /**
     * Enregistrer route PUT
     */
    public function put(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }
    
    /**
     * Enregistrer route DELETE
     */
    public function delete(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }
    
    /**
     * Ajouter une route
     */
    private function addRoute(string $method, string $path, $handler, array $middlewares = []): void
    {
        // Ajouter préfixe du groupe si applicable
        if ($this->currentGroup) {
            $path = $this->currentGroup . $path;
        }
        
        // Normaliser le chemin : 
        // - Supprimer les doubles slashes
        // - Supprimer le trailing slash (sauf pour la racine)
        $path = preg_replace('#/+#', '/', $path);  // Remplace // par /
        $path = rtrim($path, '/') ?: '/';          // Supprime trailing slash
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'pattern' => $this->compilePattern($path),
        ];
    }
    
    /**
     * Groupe de routes avec préfixe
     */
    public function group(string $prefix, callable $callback): void
    {
        $previousGroup = $this->currentGroup;
        
        // Concaténer avec le groupe parent (support des groupes imbriqués)
        if ($previousGroup) {
            $this->currentGroup = $previousGroup . $prefix;
        } else {
            $this->currentGroup = $prefix;
        }
        
        $callback($this);
        
        $this->currentGroup = $previousGroup;
    }
    
    /**
     * Compiler pattern de route (support {param})
     */
    private function compilePattern(string $path): string
    {
        // Remplacer {param} par regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Dispatcher la requête
     */
    public function dispatch(string $method, string $uri): mixed
    {
        // Nettoyer URI
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        // Garde CSRF globale (avant tout handler mutant)
        $this->enforceCsrf($method, $uri);

        // Chercher route correspondante
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extraire params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Exécuter middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $result = $this->executeMiddleware($middleware);
                    if ($result !== true) {
                        return $result; // Middleware a bloqué
                    }
                }
                
                // Exécuter handler
                return $this->executeHandler($route['handler'], $params);
            }
        }
        
        // Route non trouvée
        http_response_code(404);
        throw new RouterException('Route not found: ' . $uri);
    }
    
    /**
     * Exécuter middleware
     */
    private function executeMiddleware($middleware): mixed
    {
        if (is_callable($middleware)) {
            return $middleware();
        }
        
        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                return $instance->handle();
            }
        }
        
        throw new RouterException('Invalid middleware');
    }
    
    /**
     * Exécuter handler
     */
	private function executeHandler($handler, array $params): mixed
	{
		// CAST AUTO : string numérique → int
		foreach ($params as &$p) {
			if (ctype_digit($p)) {
				$p = (int) $p;
			}
		}

		if (is_callable($handler)) {
			return $handler(...array_values($params));
		}

		if (is_string($handler) && str_contains($handler, '@')) {
			[$class, $method] = explode('@', $handler);

			if (!class_exists($class)) {
				throw new RouterException("Controller not found: {$class}");
			}

			$controller = $this->instantiateController($class);

			if (!method_exists($controller, $method)) {
				throw new RouterException("Method not found: {$class}@{$method}");
			}

			return $controller->$method(...array_values($params));
		}

		throw new RouterException('Invalid handler');
	}
    
    /**
     * Instancier un contrôleur avec injection de dépendances
     */
    private function instantiateController(string $class): object
    {
        // Utiliser Reflection pour inspecter le constructeur
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        
        // Si pas de constructeur, instancier directement
        if (!$constructor) {
            return new $class();
        }
        
        // Résoudre les dépendances
        $dependencies = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            
            if (!$type || !$type instanceof \ReflectionNamedType) {
                throw new RouterException(
                    "Cannot resolve dependency for parameter: {$param->getName()} in {$class}"
                );
            }
            
            $typeName = $type->getName();
            
            // Chercher dans les dépendances enregistrées
            $dependency = null;
            
            // Essayer par nom de classe complet
            if (isset($this->dependencies[$typeName])) {
                $dependency = $this->dependencies[$typeName];
            }
            // Essayer par nom court (ex: Database au lieu de Framework\Services\Database)
            else {
                $shortName = substr(strrchr($typeName, '\\'), 1) ?: $typeName;
                if (isset($this->dependencies[$shortName])) {
                    $dependency = $this->dependencies[$shortName];
                }
            }
            
            if ($dependency === null) {
                throw new RouterException(
                    "Dependency not found: {$typeName} for {$class}"
                );
            }
            
            $dependencies[] = $dependency;
        }
        
        return new $class(...$dependencies);
    }
    
    /**
     * Générer URL depuis nom de route
     */
    public function url(string $name, array $params = []): string
    {
        // TODO: Implémenter named routes
        return '';
    }
    
    /**
     * Redirection
     */
    public function redirect(string $url, int $code = 302): void
    {
        header("Location: {$url}", true, $code);
        exit;
    }
}

/**
 * Exception Router
 */
class RouterException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, 404);
    }
}