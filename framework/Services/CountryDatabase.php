<?php
declare(strict_types=1);

namespace Framework\Services;

/**
 * Correspondance adresse IPv4 → pays, entièrement locale.
 *
 * POURQUOI PAS UNE API — c'est le point qui décide de tout.
 *
 * Interroger un service distant pour connaître le pays d'un visiteur est
 * tentable en une ligne. C'est aussi ce qui condamne la protection : un appel
 * HTTP sortant par requête entrante, dans le chemin critique. Sous une attaque,
 * chaque requête hostile déclenche une connexion sortante depuis VOTRE serveur.
 * Le filtre devient l'amplificateur. S'y ajoutent les quotas des services
 * gratuits — 45 appels/minute chez ip-api — qui rendent le filtre aveugle
 * précisément quand le trafic monte.
 *
 * Ici, tout est local. Les cinq registres régionaux (RIPE, ARIN, APNIC,
 * AFRINIC, LACNIC) publient chaque jour la liste de leurs attributions, en
 * texte, sans compte ni clé : environ 27 Mo, 260 000 plages, 239 pays. On les
 * convertit UNE FOIS en un fichier binaire compact.
 *
 * LE FORMAT.
 *
 *     en-tête      16 octets  : signature, version, nombre de plages, date
 *     index      1 024 octets : 256 × uint32, rang de la première plage de
 *                               chaque premier octet
 *     puis N enregistrements de 10 octets :
 *         uint32  première adresse de la plage
 *         uint32  dernière adresse de la plage
 *         char[2] code pays ISO
 *
 * Les enregistrements sont triés par adresse de début, et aucun ne franchit
 * une frontière de premier octet — ce découpage est ce qui rend la fenêtre de
 * l'index exacte. Chercher un pays revient donc à lire l'index, puis à faire
 * UNE lecture de la fenêtre correspondante (un millier de plages, une dizaine
 * de kilo-octets) parcourue par dichotomie en mémoire.
 *
 * Sans l'index, la dichotomie sur les 260 000 plages coûtait dix-huit accès
 * disque, soit 0,52 ms mesurées. Cela compte : la recherche a lieu sur CHAQUE
 * requête. Rien n'est chargé en mémoire, rien ne touche la base de données.
 */
final class CountryDatabase
{
    /** Reconnaît le fichier et sa version de format. */
    private const MAGIC   = "AEGC";
    private const VERSION = 2;

    /**
     * Index de premier octet : 256 entrées de 4 octets.
     *
     * Sans lui, chercher un pays coûtait dix-huit `fseek` + `fread` sur les
     * 260 000 plages — 0,52 ms mesurées. Comme la recherche a lieu sur CHAQUE
     * requête, l'index ramène cela à UNE lecture : il donne directement le
     * premier enregistrement susceptible de contenir une adresse commençant
     * par cet octet, et la fenêtre qui en résulte (un millier de plages, une
     * dizaine de kilo-octets) est parcourue en mémoire.
     */
    private const TAILLE_INDEX = 256 * 4;

    private const TAILLE_ENTETE = 16 + self::TAILLE_INDEX;
    private const TAILLE_ENREG  = 10;

    /** Les cinq registres régionaux et leur fichier d'attributions. */
    public const REGISTRES = [
        'ripencc' => 'https://ftp.ripe.net/pub/stats/ripencc/delegated-ripencc-latest',
        'arin'    => 'https://ftp.arin.net/pub/stats/arin/delegated-arin-extended-latest',
        'apnic'   => 'https://ftp.apnic.net/stats/apnic/delegated-apnic-latest',
        'afrinic' => 'https://ftp.afrinic.net/pub/stats/afrinic/delegated-afrinic-latest',
        'lacnic'  => 'https://ftp.lacnic.net/pub/stats/lacnic/delegated-lacnic-latest',
    ];

    private string $fichier;

    /** Descripteur gardé ouvert : plusieurs recherches par requête sont possibles. */
    private $flux = null;

    private ?int $nombre = null;

    public function __construct(?string $fichier = null)
    {
        $racine = defined('ROOT_PATH') ? rtrim(ROOT_PATH, "/\\") : dirname(__DIR__, 2);

        $this->fichier = $fichier ?? $racine . '/framework/data/geoip/ipv4-country.bin';
    }

    public function chemin(): string
    {
        return $this->fichier;
    }

    public function existe(): bool
    {
        return is_file($this->fichier) && filesize($this->fichier) > self::TAILLE_ENTETE;
    }

    /**
     * Ce que contient la base : nombre de plages et date de construction.
     *
     * @return array{existe:bool, plages:int, construite_le:?int, taille:int}
     */
    public function etat(): array
    {
        if (!$this->existe()) {
            return ['existe' => false, 'plages' => 0, 'construite_le' => null, 'taille' => 0];
        }

        $entete = (string) @file_get_contents($this->fichier, false, null, 0, 16);

        if (strlen($entete) < 16 || substr($entete, 0, 4) !== self::MAGIC) {
            return ['existe' => false, 'plages' => 0, 'construite_le' => null, 'taille' => 0];
        }

        $champs = unpack('Nversion/Nplages/Nhorodatage', substr($entete, 4, 12));

        if ((int) $champs['version'] !== self::VERSION) {
            return ['existe' => false, 'plages' => 0, 'construite_le' => null, 'taille' => 0];
        }

        return [
            'existe'        => true,
            'plages'        => (int) ($champs['plages'] ?? 0),
            'construite_le' => (int) ($champs['horodatage'] ?? 0) ?: null,
            'taille'        => (int) filesize($this->fichier),
        ];
    }

    // ── Recherche ────────────────────────────────────────────────────────

    /**
     * Le code pays ISO de l'adresse, ou null.
     *
     * Renvoie null pour toute adresse dont on ne sait rien : IPv6, adresse
     * privée, plage non attribuée, base absente. L'appelant DOIT traiter ce
     * null comme « je ne sais pas » et jamais comme « pays interdit » — sans
     * quoi une base manquante fermerait le site à tout le monde.
     */
    public function pays(string $ip): ?string
    {
        $entier = $this->versEntier($ip);

        // `ouvrir()` valide déjà la signature et la version : appeler `existe()`
        // en plus coûtait deux interrogations du système de fichiers par
        // recherche, sur un chemin parcouru à chaque requête.
        if ($entier === null || !$this->ouvrir()) {
            return null;
        }

        // L'index donne la fenêtre à examiner pour ce premier octet.
        $octet   = $entier >> 24;
        $premier = $this->index[$octet] ?? 0;
        $dernier = ($octet < 255 ? ($this->index[$octet + 1] ?? $this->nombre) : $this->nombre) - 1;
        $dernier = min($dernier, (int) $this->nombre - 1);

        if ($premier > $dernier) {
            return null;
        }

        // Une seule lecture pour toute la fenêtre : c'est là qu'est le gain.
        fseek($this->flux, self::TAILLE_ENTETE + $premier * self::TAILLE_ENREG);
        $bloc = fread($this->flux, ($dernier - $premier + 1) * self::TAILLE_ENREG);

        if (!is_string($bloc) || $bloc === '') {
            return null;
        }

        $bas  = 0;
        $haut = intdiv(strlen($bloc), self::TAILLE_ENREG) - 1;

        while ($bas <= $haut) {
            $milieu = intdiv($bas + $haut, 2);
            $pos    = $milieu * self::TAILLE_ENREG;

            $r = unpack('Ndebut/Nfin', substr($bloc, $pos, 8));

            if ($entier < $r['debut']) {
                $haut = $milieu - 1;
            } elseif ($entier > $r['fin']) {
                $bas = $milieu + 1;
            } else {
                return substr($bloc, $pos + 8, 2);
            }
        }

        return null;
    }

    /**
     * Les codes pays effectivement présents dans la base, triés.
     *
     * Sert à peupler la liste de l'administration : proposer un pays absent de
     * la base serait proposer une règle sans effet. Le parcours lit les 2,5 Mo,
     * on le mémorise donc — la base ne change qu'à la reconstruction.
     *
     * @return string[]
     */
    public function codesPresents(): array
    {
        $cle = 'framework.geoip.codes';

        if (function_exists('cache_get')) {
            $memo = cache_get($cle);
            if (is_array($memo) && $memo !== []) {
                return $memo;
            }
        }

        if (!$this->ouvrir()) {
            return [];
        }

        $codes = [];
        fseek($this->flux, self::TAILLE_ENTETE);

        // Lecture par gros blocs : 260 000 `fread` de 10 octets seraient
        // autrement le poste de dépense de cette méthode.
        while (($bloc = fread($this->flux, self::TAILLE_ENREG * 8192)) !== false && $bloc !== '') {
            for ($i = 8, $n = strlen($bloc); $i < $n; $i += self::TAILLE_ENREG) {
                $codes[substr($bloc, $i, 2)] = true;
            }
        }

        $liste = array_keys($codes);
        sort($liste);

        if (function_exists('cache_set')) {
            cache_set($cle, $liste, 86400);
        }

        return $liste;
    }

    /** À appeler après une reconstruction. */
    public function oublierCodes(): void
    {
        if (function_exists('cache_delete')) {
            try { cache_delete('framework.geoip.codes'); } catch (\Throwable $e) {}
        }
    }

    /** @var int[] */
    private array $index = [];

    /** Ouvre le fichier et charge l'index (1 Ko), une fois par processus. */
    private function ouvrir(): bool
    {
        if ($this->flux !== null) {
            return true;
        }

        $flux = @fopen($this->fichier, 'rb');
        if ($flux === false) {
            return false;
        }

        $entete = fread($flux, self::TAILLE_ENTETE);

        if (!is_string($entete) || strlen($entete) < self::TAILLE_ENTETE
            || substr($entete, 0, 4) !== self::MAGIC) {
            fclose($flux);
            return false;
        }

        $champs = unpack('Nversion/Nplages', substr($entete, 4, 8));

        // Un fichier construit par une version antérieure n'a pas le même
        // format : on refuse de le lire plutôt que d'en tirer n'importe quoi.
        if ((int) $champs['version'] !== self::VERSION) {
            fclose($flux);
            return false;
        }

        $this->flux   = $flux;
        $this->nombre = (int) $champs['plages'];
        $this->index  = array_values((array) unpack('N256', substr($entete, 16, self::TAILLE_INDEX)));

        return true;
    }

    /**
     * Convertit une IPv4 en entier non signé.
     *
     * Les adresses privées et de bouclage sont écartées d'emblée : aucun
     * registre ne les attribue, et il serait absurde de refuser l'accès à
     * quelqu'un sur le réseau local.
     */
    private function versEntier(string $ip): ?int
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return null;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        $entier = ip2long($ip);

        // ip2long rend un entier signé sur les plateformes 32 bits.
        return $entier === false ? null : ($entier & 0xFFFFFFFF);
    }

    // ── Construction ─────────────────────────────────────────────────────

    /**
     * Reconstruit la base depuis les fichiers des registres.
     *
     * @param callable|null $journal Reçoit une ligne d'avancement.
     * @return array{success:bool, message:string, plages?:int, taille?:int}
     */
    public function construire(?callable $journal = null): array
    {
        $dire = $journal ?? static function (string $l): void {};

        $plages = [];
        $echecs = [];

        foreach (self::REGISTRES as $nom => $url) {
            $dire("Téléchargement de {$nom}…");

            $contenu = $this->telecharger($url);
            if ($contenu === null) {
                $echecs[] = $nom;
                $dire('  ⚠️ ' . $nom . ' injoignable : ' . ($this->dernierMotif ?: 'motif inconnu'));
                continue;
            }

            $avant = count($plages);
            $this->analyser($contenu, $plages);
            $dire(sprintf('  %s : %s plages', $nom, number_format(count($plages) - $avant, 0, ',', ' ')));

            unset($contenu);
        }

        // Un seul registre manquant laisserait un continent entier hors de la
        // base, donc considéré « pays inconnu » — et une liste blanche
        // deviendrait un mur. Mieux vaut refuser une base incomplète.
        if (count($echecs) > 0) {
            return [
                'success' => false,
                'message' => 'Registre(s) injoignable(s) : ' . implode(', ', $echecs)
                           . '. La base n\'a pas été remplacée — une base partielle bloquerait des pays entiers à tort.',
            ];
        }

        if (count($plages) < 100000) {
            return [
                'success' => false,
                'message' => sprintf('Données suspectes : %d plages seulement. La base n\'a pas été remplacée.', count($plages)),
            ];
        }

        $dire('Tri des plages…');
        usort($plages, static fn(array $a, array $b): int => $a[0] <=> $b[0]);

        $dire('Écriture du fichier…');

        return $this->ecrire($plages);
    }

    /**
     * Extrait les plages IPv4 d'un fichier de registre.
     *
     * Format : `registre|pays|type|début|nombre|date|statut`. Les lignes de
     * résumé portent `*` comme pays, les plages réservées ou disponibles n'ont
     * pas de pays : les unes comme les autres sont écartées.
     *
     * @param array<int,array{0:int,1:int,2:string}> $plages
     */
    private function analyser(string $contenu, array &$plages): void
    {
        foreach (explode("\n", $contenu) as $ligne) {
            if ($ligne === '' || $ligne[0] === '#') {
                continue;
            }

            $c = explode('|', $ligne);
            if (count($c) < 5 || $c[2] !== 'ipv4') {
                continue;
            }

            $pays = strtoupper(trim($c[1]));
            if (strlen($pays) !== 2 || !ctype_alpha($pays)) {
                continue;
            }

            $debut = ip2long(trim($c[3]));
            $taille = (int) $c[4];

            if ($debut === false || $taille < 1) {
                continue;
            }

            $debut &= 0xFFFFFFFF;
            $fin    = $debut + $taille - 1;

            // Découpage aux frontières de premier octet, fait ICI plutôt qu'en
            // seconde passe : un second tableau de 260 000 entrées doublait la
            // mémoire et dépassait la limite de PHP.
            $premier = $debut >> 24;
            $dernier = $fin   >> 24;

            if ($premier === $dernier) {
                $plages[] = [$debut, $fin, $pays];
                continue;
            }

            for ($o = $premier; $o <= $dernier; $o++) {
                $plages[] = [max($debut, $o << 24), min($fin, ($o << 24) | 0xFFFFFF), $pays];
            }
        }
    }

    /** @param array<int,array{0:int,1:int,2:string}> $plages */
    private function ecrire(array $plages): array
    {
        $dossier = dirname($this->fichier);

        if (!is_dir($dossier) && !@mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            return ['success' => false, 'message' => 'Impossible de créer ' . $dossier];
        }

        $this->protegerDossier($dossier);

        // Écriture dans un fichier temporaire puis renommage : une construction
        // interrompue ne doit jamais laisser une base tronquée en service.
        $temporaire = $this->fichier . '.tmp';
        $sortie = @fopen($temporaire, 'wb');

        if ($sortie === false) {
            return ['success' => false, 'message' => 'Impossible d\'écrire dans ' . $dossier];
        }

        $total = count($plages);

        // Index par premier octet : rang du PREMIER enregistrement de l'octet.
        // Chaque plage tient entièrement dans un octet (découpage fait à
        // l'analyse), donc `debut >> 24` ne décroît jamais et la fenêtre
        // contient exactement les plages de cet octet — ni plus, ni moins.
        $index = [];
        foreach ($plages as $rang => [$debut, , ]) {
            $octet = $debut >> 24;
            if (!isset($index[$octet])) {
                $index[$octet] = $rang;
            }
        }

        // Un octet sans aucune attribution hérite du rang de l'octet suivant :
        // sa fenêtre est alors vide et la recherche répond sans rien lire.
        $table   = array_fill(0, 256, $total);
        $suivant = $total;
        for ($o = 255; $o >= 0; $o--) {
            $suivant = $index[$o] ?? $suivant;
            $table[$o] = $suivant;
        }

        fwrite($sortie, self::MAGIC . pack('NNN', self::VERSION, $total, time()));
        fwrite($sortie, pack('N256', ...$table));

        foreach ($plages as [$debut, $fin, $pays]) {
            fwrite($sortie, pack('NN', $debut, $fin) . $pays);
        }

        fclose($sortie);

        // Le descripteur ouvert pointe sur l'ancien fichier.
        if ($this->flux !== null) { fclose($this->flux); $this->flux = null; $this->nombre = null; }

        if (!@rename($temporaire, $this->fichier)) {
            @unlink($temporaire);
            return ['success' => false, 'message' => 'Le remplacement de la base a échoué.'];
        }

        return [
            'success' => true,
            'message' => sprintf(
                'Base construite : %s plages, %s Ko.',
                number_format(count($plages), 0, ',', ' '),
                number_format(filesize($this->fichier) / 1024, 0, ',', ' ')
            ),
            'plages' => count($plages),
            'taille' => (int) filesize($this->fichier),
        ];
    }

    /** Le fichier ne doit pas être servi par le serveur web. */
    private function protegerDossier(string $dossier): void
    {
        $htaccess = $dossier . '/.htaccess';

        if (!is_file($htaccess)) {
            @file_put_contents(
                $htaccess,
                "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
              . "<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n"
            );
        }
    }

    /** Dernier motif d'échec rencontré, pour un diagnostic utile à l'écran. */
    private string $dernierMotif = '';

    public function dernierMotif(): string
    {
        return $this->dernierMotif;
    }

    /**
     * Récupère un fichier de registre.
     *
     * Deux mécanismes, essayés dans cet ordre, parce qu'ils ne trouvent PAS les
     * certificats racine au même endroit : sur cette machine, le flux natif
     * valide correctement les certificats alors que cURL échoue faute de
     * `curl.cainfo` renseigné — configuration par défaut de WAMP. L'inverse
     * s'observe ailleurs. Essayer les deux évite d'imposer une modification de
     * php.ini pour une fonctionnalité d'administration.
     */
    private function telecharger(string $url): ?string
    {
        $corps = @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 120, 'user_agent' => 'Aegis-Framework'],
        ]));

        if (is_string($corps) && $corps !== '') {
            return $corps;
        }

        $motif = (string) (error_get_last()['message'] ?? '');

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_USERAGENT      => 'Aegis-Framework',
            ];

            // cURL ne consulte pas le magasin de certificats du système sous
            // Windows : sans `curl.cainfo`, toute connexion HTTPS échoue avec
            // « unable to get local issuer certificate ». On lui désigne un
            // paquet si on en trouve un — jamais en désactivant la
            // vérification, ce qui reviendrait à accepter n'importe quel
            // serveur se faisant passer pour un registre.
            $ca = $this->paquetDeCertificats();
            if ($ca !== null) {
                $options[CURLOPT_CAINFO] = $ca;
            }

            curl_setopt_array($ch, $options);

            $corps = curl_exec($ch);
            $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err   = curl_error($ch);

            if ($code === 200 && is_string($corps) && $corps !== '') {
                return $corps;
            }

            $motif = $err !== '' ? $err : ('HTTP ' . $code);
        }

        $this->dernierMotif = $motif;

        return null;
    }

    /** Un paquet de certificats racine utilisable par cURL, s'il en existe un. */
    private function paquetDeCertificats(): ?string
    {
        foreach ([ini_get('curl.cainfo'), ini_get('openssl.cafile')] as $declare) {
            $declare = trim((string) $declare);
            if ($declare !== '' && is_file($declare)) {
                return $declare;
            }
        }

        $candidats = [
            '/etc/ssl/certs/ca-certificates.crt',        // Debian, Ubuntu
            '/etc/pki/tls/certs/ca-bundle.crt',          // RedHat, Fedora
            '/etc/ssl/cert.pem',                         // Alpine, BSD, macOS
        ];

        // WAMP et XAMPP déposent un paquet à côté du binaire PHP, sans
        // toujours le déclarer dans php.ini.
        $racinePhp = dirname(PHP_BINARY);
        foreach (['/cacert.pem', '/extras/ssl/cacert.pem', '/../extras/ssl/cacert.pem'] as $relatif) {
            $candidats[] = $racinePhp . $relatif;
        }

        foreach ($candidats as $chemin) {
            if (is_file($chemin)) {
                return $chemin;
            }
        }

        return null;
    }
}
