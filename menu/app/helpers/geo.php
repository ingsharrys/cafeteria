<?php
/**
 * geo.php
 * Geocerca: el ecommerce solo funciona dentro de un radio alrededor del colegio.
 *
 * Coordenadas por defecto: COLEGIO RAFAEL POMBO (Neiva).
 * Se pueden sobreescribir en el .env con:
 *   GEO_LAT, GEO_LNG, GEO_RADIO_M
 */

require_once __DIR__ . '/menu_access.php'; // para menu_env()

if (!function_exists('geo_config')) {

    function geo_config(): array
    {
        return [
            'lat'    => (float) menu_env('GEO_LAT', 2.9240484),
            'lng'    => (float) menu_env('GEO_LNG', -75.2846054),
            'radio'  => (float) menu_env('GEO_RADIO_M', 2000), // metros
        ];
    }

    /**
     * Distancia en metros entre dos coordenadas (fórmula de Haversine).
     */
    function geo_distancia_m(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000.0; // radio de la Tierra en metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    /**
     * ¿Las coordenadas están dentro del radio permitido del colegio?
     */
    function geo_dentro_del_rango(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null || ($lat == 0.0 && $lng == 0.0)) {
            return false;
        }
        $c = geo_config();
        return geo_distancia_m($lat, $lng, $c['lat'], $c['lng']) <= $c['radio'];
    }
}
