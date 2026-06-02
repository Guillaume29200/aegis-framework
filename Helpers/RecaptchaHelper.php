<?php
/**
 * Helper reCAPTCHA
 * 
 * Fonctions helper pour faciliter l'intégration reCAPTCHA dans les vues
 */

/**
 * Afficher le script reCAPTCHA
 * À placer dans le <head> ou avant </body>
 */
function recaptcha_script(): void
{
    global $container;
    
    if (!isset($container)) {
        echo '<!-- reCAPTCHA: Container non disponible -->';
        return;
    }
    
    try {
        $recaptcha = $container->get('Framework\\Services\\RecaptchaService');
        echo $recaptcha->renderScript();
    } catch (\Exception $e) {
        echo '<!-- reCAPTCHA: Service non disponible -->';
    }
}

/**
 * Vérifier si reCAPTCHA est actif pour une zone
 */
function recaptcha_active(string $zone): bool
{
    global $container;
    
    if (!isset($container)) {
        return false;
    }
    
    try {
        $recaptcha = $container->get('Framework\\Services\\RecaptchaService');
        return $recaptcha->isActiveForZone($zone);
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Obtenir la Site Key
 */
function recaptcha_site_key(): string
{
    global $container;
    
    if (!isset($container)) {
        return '';
    }
    
    try {
        $recaptcha = $container->get('Framework\\Services\\RecaptchaService');
        return $recaptcha->getSiteKey();
    } catch (\Exception $e) {
        return '';
    }
}

/**
 * Générer le code JavaScript pour exécuter reCAPTCHA sur un formulaire
 * 
 * @param string $formId ID du formulaire
 * @param string $action Action reCAPTCHA (login, register, etc.)
 */
function recaptcha_form_handler(string $formId, string $action): void
{
    global $container;
    
    if (!isset($container)) {
        echo '<!-- reCAPTCHA: Container non disponible -->';
        return;
    }
    
    try {
        $recaptcha = $container->get('Framework\\Services\\RecaptchaService');
        
        if (!$recaptcha->isActiveForZone($action)) {
            // Si pas actif, laisser le formulaire se soumettre normalement
            echo sprintf(
                '<script>
                    // reCAPTCHA désactivé pour cette zone
                    console.log("reCAPTCHA: Désactivé pour la zone %s");
                </script>',
                htmlspecialchars($action, ENT_QUOTES, 'UTF-8')
            );
            return;
        }
        
        $siteKey = $recaptcha->getSiteKey();
        
        echo sprintf(
            '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    const form = document.getElementById("%s");
                    if (!form) {
                        console.error("Formulaire #%s introuvable");
                        return;
                    }
                    
                    form.addEventListener("submit", function(e) {
                        e.preventDefault();
                        
                        // Désactiver le bouton submit
                        const submitBtn = form.querySelector("button[type=submit]");
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.classList.add("loading");
                        }
                        
                        // Exécuter reCAPTCHA
                        grecaptcha.ready(function() {
                            grecaptcha.execute("%s", {action: "%s"}).then(function(token) {
                                // Ajouter le token au formulaire
                                let tokenInput = form.querySelector("input[name=recaptcha_token]");
                                if (!tokenInput) {
                                    tokenInput = document.createElement("input");
                                    tokenInput.type = "hidden";
                                    tokenInput.name = "recaptcha_token";
                                    form.appendChild(tokenInput);
                                }
                                tokenInput.value = token;
                                
                                // Soumettre le formulaire
                                form.submit();
                            }).catch(function(error) {
                                console.error("reCAPTCHA error:", error);
                                alert("Erreur reCAPTCHA. Veuillez réessayer.");
                                
                                // Réactiver le bouton
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.classList.remove("loading");
                                }
                            });
                        });
                    });
                });
            </script>',
            htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($action, ENT_QUOTES, 'UTF-8')
        );
    } catch (\Exception $e) {
        echo '<!-- reCAPTCHA: Erreur lors de la génération du handler -->';
    }
}

/**
 * Badge reCAPTCHA (à afficher en bas de page si actif)
 */
function recaptcha_badge(): void
{
    global $container;
    
    if (!isset($container)) {
        return;
    }
    
    try {
        $recaptcha = $container->get('Framework\\Services\\RecaptchaService');
        
        if (!$recaptcha->isEnabled() || !$recaptcha->isConfigured()) {
            return;
        }
        
        echo '
        <div style="position: fixed; bottom: 10px; right: 10px; z-index: 9999; font-size: 11px; color: #999;">
            Ce site est protégé par reCAPTCHA<br>
            <a href="https://policies.google.com/privacy" target="_blank" style="color: #999;">Confidentialité</a> - 
            <a href="https://policies.google.com/terms" target="_blank" style="color: #999;">Conditions</a>
        </div>';
    } catch (\Exception $e) {
        // Silencieux
    }
}
