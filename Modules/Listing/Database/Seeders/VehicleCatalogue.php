<?php

namespace Modules\Listing\Database\Seeders;

/**
 * The makes and models the vehicle section starts with: what actually sells in
 * Azerbaijan, in the order the local sites list them. It is a starting point,
 * not a closed list — the admin panel adds a make or a model in a few clicks,
 * and every ad keeps working while the list grows.
 */
class VehicleCatalogue
{
    /** @return array<string, array{0: string, 1: array<int, string>}> */
    public static function makes(): array
    {
        return [
            'mercedes-benz' => ['Mercedes-Benz', ['A 180', 'C 180', 'C 200', 'C 220', 'CLA 250', 'E 200', 'E 220', 'E 230', 'E 240', 'E 250', 'E 270', 'GLA 250', 'GLC 300', 'GLE 350', 'ML 350', 'S 350', 'S 500', 'Sprinter', 'Vito']],
            'bmw' => ['BMW', ['116', '320', '328', '330', '520', '523', '525', '528', '530', '535', '540', '730', '740', 'X1', 'X3', 'X4', 'X5', 'X6', 'X7']],
            'lada-vaz' => ['LADA (VAZ)', ['2103', '2104', '2105', '2106', '2107', '2109', '21099', '2110', '2114', '2115', 'Granta', 'Largus', 'Niva', 'Priora', 'Vesta']],
            'hyundai' => ['Hyundai', ['Accent', 'Elantra', 'Getz', 'i10', 'i20', 'i30', 'Ioniq', 'Kona', 'Santa Fe', 'Sonata', 'Tucson']],
            'kia' => ['Kia', ['Cerato', 'K5', 'Optima', 'Picanto', 'Rio', 'Seltos', 'Sorento', 'Soul', 'Sportage']],
            'toyota' => ['Toyota', ['Avalon', 'Camry', 'Corolla', 'Highlander', 'Land Cruiser', 'Prado', 'Prius', 'RAV4', 'Yaris']],
            'chevrolet' => ['Chevrolet', ['Aveo', 'Captiva', 'Cobalt', 'Cruze', 'Equinox', 'Lacetti', 'Malibu', 'Spark', 'Tahoe']],
            'nissan' => ['Nissan', ['Altima', 'Juke', 'Leaf', 'Maxima', 'Micra', 'Patrol', 'Qashqai', 'Sunny', 'X-Trail']],
            'volkswagen' => ['Volkswagen', ['Amarok', 'Caddy', 'Golf', 'ID.4', 'Jetta', 'Passat', 'Polo', 'Tiguan', 'Touareg', 'Transporter']],
            'ford' => ['Ford', ['Escape', 'Explorer', 'Fiesta', 'Focus', 'Fusion', 'Mondeo', 'Ranger', 'Transit']],
            'opel' => ['Opel', ['Astra', 'Corsa', 'Insignia', 'Mokka', 'Omega', 'Vectra', 'Zafira']],
            'audi' => ['Audi', ['A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'Q3', 'Q5', 'Q7', 'Q8']],
            'lexus' => ['Lexus', ['ES 300', 'ES 350', 'GX 460', 'IS 250', 'LX 570', 'NX 300', 'RX 300', 'RX 350', 'UX 200']],
            'honda' => ['Honda', ['Accord', 'Civic', 'CR-V', 'Fit', 'HR-V', 'Pilot']],
            'mazda' => ['Mazda', ['3', '6', 'CX-3', 'CX-5', 'CX-7', 'CX-9']],
            'renault' => ['Renault', ['Captur', 'Duster', 'Fluence', 'Logan', 'Megane', 'Sandero', 'Symbol']],
            'peugeot' => ['Peugeot', ['206', '207', '301', '307', '308', '408', '508', '2008', '3008']],
            'skoda' => ['Skoda', ['Fabia', 'Kodiaq', 'Octavia', 'Rapid', 'Superb', 'Yeti']],
            'mitsubishi' => ['Mitsubishi', ['ASX', 'Lancer', 'Outlander', 'Pajero']],
            'land-rover' => ['Land Rover', ['Defender', 'Discovery', 'Freelander', 'Range Rover', 'Range Rover Evoque', 'Range Rover Sport', 'Range Rover Velar']],
            'porsche' => ['Porsche', ['Cayenne', 'Macan', 'Panamera', 'Taycan']],
            'tesla' => ['Tesla', ['Model 3', 'Model S', 'Model X', 'Model Y']],
            'byd' => ['BYD', ['Atto 3', 'Dolphin', 'Han', 'Qin', 'Seal', 'Song', 'Tang', 'Yuan']],
            'chery' => ['Chery', ['Arrizo 5', 'Tiggo 2', 'Tiggo 4', 'Tiggo 7', 'Tiggo 8']],
            'changan' => ['Changan', ['CS35', 'CS55', 'CS75', 'Eado', 'UNI-K', 'UNI-T']],
            'haval' => ['Haval', ['Dargo', 'H6', 'Jolion', 'M6']],
            'geely' => ['Geely', ['Atlas', 'Coolray', 'Emgrand', 'Monjaro', 'Tugella']],
            'jetour' => ['Jetour', ['Dashing', 'T2', 'X70', 'X90']],
            'zeekr' => ['Zeekr', ['001', '007', 'X']],
            'gaz' => ['GAZ', ['3110', 'Gazel', 'Sobol', 'Volga']],
            'uaz' => ['UAZ', ['Hunter', 'Patriot', '469']],
            'other' => ['Digər', ['Digər']],
        ];
    }
}
