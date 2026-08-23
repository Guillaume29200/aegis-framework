<?php
declare(strict_types=1);

namespace Framework\Capabilities;

/**
 * Rend en HTML ce que les capacités doivent poser sur une page publique.
 *
 * Jusqu'ici, cocher « RGPD / Cookies » dans le générateur chargeait bien le
 * helper `cookie_banner()` — mais personne ne l'appelait, et aucune page
 * publique n'était générée où le faire. La capacité était documentée, pas
 * câblée : le bandeau ne s'affichait jamais. Même chose pour Analytics, SEO
 * et reCAPTCHA.
 *
 * Cette classe est le chaînon manquant. Elle rassemble ce qui va dans le
 * <head> et ce qui va juste avant </body>, et le rend sous forme de chaînes —
 * ce qui permet de les passer à un gabarit HTML, où aucun PHP ne s'exécute :
 *
 *     $caps = CapabilityOutput::forPage(['title' => $titre, 'description' => $desc]);
 *     // dans header.html : {{{ head_extra }}}
 *     // dans footer.html : {{{ body_end }}}
 *
 * Chaque brique n'est posée que si son helper est chargé, c'est-à-dire si un
 * module actif déclare la capacité correspondante. Une capacité non cochée ne
 * coûte donc rien, et une capacité cochée mais désactivée en configuration se
 * tait d'elle-même.
 */
final class CapabilityOutput
{
    /**
     * @param array $seo Contexte SEO de la page : title, description, image, url…
     * @return array{head_extra:string, body_end:string}
     */
    public static function forPage(array $seo = []): array
    {
        return [
            'head_extra' => self::head($seo),
            'body_end'   => self::bodyEnd(),
        ];
    }

    /** Balises méta, tracker d'audience et script anti-bot. */
    public static function head(array $seo = []): string
    {
        $out = '';

        if (function_exists('seo_head')) {
            $out .= (string) seo_head($seo);
        }
        if (function_exists('analytics_head')) {
            $out .= (string) analytics_head();
        }
        // recaptcha_script() écrit directement dans la sortie : on la capture
        // pour pouvoir la remettre à un gabarit plutôt qu'à l'écran.
        if (function_exists('recaptcha_script')) {
            $out .= self::capture('recaptcha_script');
        }

        return $out;
    }

    /** Ce qui se pose en fin de page : bandeau de consentement, TurboNav. */
    public static function bodyEnd(): string
    {
        $out = function_exists('cookie_banner') ? self::capture('cookie_banner') : '';

        // TurboNav vient en dernier : il remplace le corps de la page, autant
        // qu'il trouve tout en place quand il s'installe.
        if (function_exists('turbonav_script')) {
            $out .= (string) turbonav_script();
        }

        return $out;
    }

    /**
     * Exécute un helper qui écrit dans la sortie et renvoie ce qu'il a écrit.
     *
     * Le tampon est refermé même si le helper lève : sans ce filet, une erreur
     * dans une brique transverse avalerait le reste de la page.
     */
    private static function capture(string $fn): string
    {
        ob_start();
        try {
            $fn();
        } catch (\Throwable $e) {
            // Une capacité qui échoue ne doit pas emporter la page avec elle.
            error_log('[CapabilityOutput] ' . $fn . ' : ' . $e->getMessage());
        }
        return (string) ob_get_clean();
    }
}
