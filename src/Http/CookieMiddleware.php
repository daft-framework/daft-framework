<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Http;

use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftRouter\DaftMiddleware;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieMiddleware implements DaftMiddleware
{
    public static function DaftRouterMiddlewareHandler(
        Request $request,
        ? Response $response
    ) : ? Response {
        if ( ! is_null($response)) {
            $framework = Framework::ObtainFrameworkForRequest($request);

            $config = $framework->ObtainConfig();

            if ( ! isset($config[self::class])) {
                return $response;
            }

            $config = (array) $config[self::class];

            $configSecure = (bool) ($config['secure'] ?? null);
            $configHttpOnly = (bool) ($config['httpOnly'] ?? null);

            /**
            * @var string|null $configSameSite
            */
            $configSameSite = is_string($config['sameSite'] ?? null) ? $config['sameSite'] : null;

            self::PerhapsReconfigureResponseCookies(
                $response,
                $configSecure,
                $configHttpOnly,
                $configSameSite
            );
        }

        return $response;
    }

    public static function DaftRouterRoutePrefixExceptions() : array
    {
        return [];
    }

    public static function PerhapsReconfigureResponseCookies(
        Response $response,
        bool $configSecure,
        bool $configHttpOnly,
        ? string $configSameSite
    ) : void {
        /**
        * @var Cookie $cookie
        */
        foreach ($response->headers->getCookies() as $cookie) {
            self::PerhapsReconfigureCookie(
                $response,
                $cookie,
                $configSecure,
                $configHttpOnly,
                $configSameSite
            );
        }
    }

    public static function PerhapsReconfigureCookie(
        Response $response,
        Cookie $cookie,
        bool $configSecure,
        bool $configHttpOnly,
        ? string $configSameSite
    ) : void {
        $updateSecure = $cookie->isSecure() !== $configSecure;
        $updateHttpOnly = $cookie->isHttpOnly() !== $configHttpOnly;
        $updateSameSite = $cookie->getSameSite() !== $configSameSite;

        if ($updateSecure || $updateHttpOnly || $updateSameSite) {
            static::ReconfigureCookie(
                $response,
                $cookie,
                $configSecure,
                $configHttpOnly,
                $configSameSite
            );
        }
    }

    public static function ReconfigureCookie(
        Response $response,
        Cookie $cookie,
        bool $configSecure,
        bool $configHttpOnly,
        ? string $configSameSite
    ) : void {
            $cookieName = $cookie->getName();
            $cookiePath = $cookie->getPath();
            $cookieDomain = $cookie->getDomain();
            $response->headers->removeCookie($cookieName, $cookiePath, $cookieDomain);
            $response->headers->setCookie(new Cookie(
                $cookieName,
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookiePath,
                $cookieDomain,
                $configSecure,
                $configHttpOnly,
                $cookie->isRaw(),
                $configSameSite
            ));
    }
}
