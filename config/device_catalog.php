<?php

/*
|--------------------------------------------------------------------------
| Catálogo de dispositivos para Órdenes de Servicio
|--------------------------------------------------------------------------
| Centraliza las relaciones tipo -> marca -> modelo usadas por los
| formularios de alta y edición. La vista agrega la opción "Otro" para
| conservar compatibilidad con equipos que todavía no estén catalogados.
*/
return [
    'Teléfono celular' => [
        'Apple' => [
            'iPhone 17 Pro Max', 'iPhone 17 Pro', 'iPhone 17', 'iPhone Air',
            'iPhone 17e', 'iPhone 16 Pro Max', 'iPhone 16 Pro', 'iPhone 16 Plus',
            'iPhone 16', 'iPhone 16e', 'iPhone 15 Pro Max', 'iPhone 15 Pro',
            'iPhone 15 Plus', 'iPhone 15', 'iPhone 14 Pro Max', 'iPhone 14 Pro',
            'iPhone 14 Plus', 'iPhone 14', 'iPhone 13 Pro Max', 'iPhone 13 Pro',
            'iPhone 13', 'iPhone 13 mini', 'iPhone 12 Pro Max', 'iPhone 12 Pro',
            'iPhone 12', 'iPhone 12 mini', 'iPhone 11 Pro Max', 'iPhone 11 Pro',
            'iPhone 11', 'iPhone SE', 'iPhone XS Max', 'iPhone XS', 'iPhone XR',
            'iPhone X', 'iPhone 8 Plus', 'iPhone 8', 'iPhone 7 Plus', 'iPhone 7',
        ],
        'Samsung' => [
            'Galaxy S26 Ultra', 'Galaxy S26+', 'Galaxy S26', 'Galaxy S25 Ultra',
            'Galaxy S25+', 'Galaxy S25', 'Galaxy S25 FE', 'Galaxy S24 Ultra',
            'Galaxy S24+', 'Galaxy S24', 'Galaxy S24 FE', 'Galaxy Z Fold7',
            'Galaxy Z Flip7', 'Galaxy Z Fold6', 'Galaxy Z Flip6', 'Galaxy A56 5G',
            'Galaxy A36 5G', 'Galaxy A26 5G', 'Galaxy A17 5G', 'Galaxy A16',
            'Galaxy A55 5G', 'Galaxy A35 5G', 'Galaxy A25 5G', 'Galaxy A15',
            'Galaxy A54 5G', 'Galaxy A34 5G', 'Galaxy A14', 'Galaxy M55 5G',
            'Galaxy M35 5G', 'Galaxy XCover7',
        ],
        'Google' => [
            'Pixel 10 Pro Fold', 'Pixel 10 Pro XL', 'Pixel 10 Pro', 'Pixel 10',
            'Pixel 10a', 'Pixel 9 Pro Fold', 'Pixel 9 Pro XL', 'Pixel 9 Pro',
            'Pixel 9', 'Pixel 9a', 'Pixel 8 Pro', 'Pixel 8', 'Pixel 8a',
            'Pixel 7 Pro', 'Pixel 7', 'Pixel 7a', 'Pixel 6 Pro', 'Pixel 6', 'Pixel 6a',
        ],
        'Motorola' => [
            'motorola razr 60 ultra', 'motorola razr 60', 'motorola razr 50 ultra',
            'motorola razr 50', 'motorola edge 60 pro', 'motorola edge 60 fusion',
            'motorola edge 50 ultra', 'motorola edge 50 pro', 'motorola edge 50 fusion',
            'moto g86 5G', 'moto g85 5G', 'moto g75 5G', 'moto g56 5G',
            'moto g55 5G', 'moto g35 5G', 'moto g24', 'moto g14', 'moto e15',
            'moto e14', 'ThinkPhone',
        ],
        'Xiaomi' => [
            'Xiaomi 15 Ultra', 'Xiaomi 15 Pro', 'Xiaomi 15', 'Xiaomi 14 Ultra',
            'Xiaomi 14T Pro', 'Xiaomi 14T', 'Xiaomi 14', 'Xiaomi 13T Pro',
            'Xiaomi 13T', 'Xiaomi 13', 'Xiaomi MIX Flip', 'Xiaomi MIX Fold 4',
        ],
        'Redmi' => [
            'Redmi Note 15 Pro+ 5G', 'Redmi Note 15 Pro 5G', 'Redmi Note 15 5G',
            'Redmi Note 14 Pro+ 5G', 'Redmi Note 14 Pro 5G', 'Redmi Note 14',
            'Redmi Note 13 Pro+ 5G', 'Redmi Note 13 Pro 5G', 'Redmi Note 13',
            'Redmi 14C', 'Redmi 13', 'Redmi 13C', 'Redmi A5', 'Redmi A3',
        ],
        'POCO' => [
            'POCO F7 Ultra', 'POCO F7 Pro', 'POCO F7', 'POCO F6 Pro', 'POCO F6',
            'POCO X7 Pro', 'POCO X7', 'POCO X6 Pro', 'POCO X6', 'POCO M7 Pro 5G',
            'POCO M6 Pro', 'POCO C75', 'POCO C65',
        ],
        'Huawei' => [
            'Pura 80 Ultra', 'Pura 80 Pro', 'Pura 80', 'Pura 70 Ultra',
            'Pura 70 Pro', 'Pura 70', 'Mate 70 Pro', 'Mate 70', 'Mate X6',
            'nova 13 Pro', 'nova 13', 'nova 12s', 'nova 11i',
        ],
        'HONOR' => [
            'HONOR Magic7 Pro', 'HONOR Magic7', 'HONOR Magic V3', 'HONOR 400 Pro',
            'HONOR 400', 'HONOR 200 Pro', 'HONOR 200', 'HONOR X9c', 'HONOR X8c',
            'HONOR X7c', 'HONOR X6b',
        ],
        'OPPO' => [
            'Find X8 Pro', 'Find X8', 'Find N5', 'Reno14 Pro 5G', 'Reno14 5G',
            'Reno13 Pro 5G', 'Reno13 5G', 'Reno12 5G', 'A5 Pro 5G', 'A5 5G',
            'A3 Pro 5G', 'A3', 'A60',
        ],
        'OnePlus' => [
            'OnePlus 13', 'OnePlus 13R', 'OnePlus 12', 'OnePlus 12R',
            'OnePlus Open', 'OnePlus Nord 5', 'OnePlus Nord 4', 'OnePlus Nord CE4',
        ],
        'realme' => [
            'realme GT 7 Pro', 'realme GT 7', 'realme GT 6', 'realme 14 Pro+ 5G',
            'realme 14 Pro 5G', 'realme 13 Pro+ 5G', 'realme 13 5G',
            'realme C75', 'realme C65', 'realme Note 60',
        ],
        'vivo' => [
            'vivo X200 Pro', 'vivo X200', 'vivo X Fold3 Pro', 'vivo V50',
            'vivo V40', 'vivo V30', 'vivo Y39 5G', 'vivo Y29', 'vivo Y19s',
        ],
        'Nothing' => [
            'Phone (3)', 'Phone (3a) Pro', 'Phone (3a)', 'Phone (2)',
            'Phone (2a) Plus', 'Phone (2a)', 'CMF Phone 2 Pro', 'CMF Phone 1',
        ],
        'Sony' => [
            'Xperia 1 VII', 'Xperia 1 VI', 'Xperia 5 V', 'Xperia 10 VII',
            'Xperia 10 VI', 'Xperia PRO-I',
        ],
        'ASUS' => [
            'ROG Phone 9 Pro', 'ROG Phone 9', 'ROG Phone 8 Pro',
            'Zenfone 12 Ultra', 'Zenfone 11 Ultra',
        ],
        'ZTE' => [
            'nubia Z70 Ultra', 'nubia Z60 Ultra', 'nubia Flip 2', 'REDMAGIC 10 Pro',
            'Blade V70', 'Blade A75 5G', 'Blade A55',
        ],
        'TCL' => [
            'TCL 60 NXTPAPER', 'TCL 50 Pro NXTPAPER 5G', 'TCL 50 5G',
            'TCL 40 NXTPAPER', 'TCL 40 SE',
        ],
        'HMD / Nokia' => [
            'HMD Skyline', 'HMD Fusion', 'HMD Pulse Pro', 'HMD Pulse+',
            'Nokia G42 5G', 'Nokia G22', 'Nokia C32',
        ],
    ],

    'Tableta' => [
        'Apple' => [
            'iPad Pro 13 pulgadas (M5)', 'iPad Pro 11 pulgadas (M5)',
            'iPad Air 13 pulgadas (M3)', 'iPad Air 11 pulgadas (M3)',
            'iPad (A16)', 'iPad mini (A17 Pro)', 'iPad Pro (M4)',
            'iPad Air (M2)', 'iPad 10.ª generación', 'iPad 9.ª generación',
        ],
        'Samsung' => [
            'Galaxy Tab S11 Ultra', 'Galaxy Tab S11', 'Galaxy Tab S10 Ultra',
            'Galaxy Tab S10+', 'Galaxy Tab S10 FE+', 'Galaxy Tab S10 FE',
            'Galaxy Tab S9 Ultra', 'Galaxy Tab S9+', 'Galaxy Tab S9',
            'Galaxy Tab A9+', 'Galaxy Tab A9', 'Galaxy Tab Active5',
        ],
        'Lenovo' => [
            'Lenovo Yoga Tab Plus', 'Lenovo Tab P12', 'Lenovo Tab Plus',
            'Lenovo Tab M11', 'Lenovo Tab M10', 'Lenovo Legion Tab',
        ],
        'Huawei' => [
            'MatePad Pro 13.2', 'MatePad Pro 12.2', 'MatePad 12 X',
            'MatePad 11.5 S', 'MatePad SE 11', 'MatePad T10s',
        ],
        'Xiaomi' => [
            'Xiaomi Pad 7 Ultra', 'Xiaomi Pad 7 Pro', 'Xiaomi Pad 7',
            'Xiaomi Pad 6S Pro', 'Xiaomi Pad 6',
        ],
        'Redmi' => ['Redmi Pad Pro', 'Redmi Pad 2', 'Redmi Pad SE', 'Redmi Pad'],
        'HONOR' => ['HONOR MagicPad 2', 'HONOR Pad 10', 'HONOR Pad 9', 'HONOR Pad X9'],
        'Microsoft' => ['Surface Pro 11', 'Surface Pro 10', 'Surface Go 4', 'Surface Pro 9'],
        'Amazon' => ['Fire Max 11', 'Fire HD 10', 'Fire HD 8', 'Fire 7'],
        'TCL' => ['TCL NXTPAPER 14', 'TCL NXTPAPER 11 Plus', 'TCL TAB 10 Gen 2'],
    ],

    'Laptop' => [
        'Apple' => [
            'MacBook Air 13 pulgadas (M4)', 'MacBook Air 15 pulgadas (M4)',
            'MacBook Pro 14 pulgadas (M4)', 'MacBook Pro 16 pulgadas (M4)',
            'MacBook Air (M3)', 'MacBook Pro (M3)', 'MacBook Air (M2)',
            'MacBook Pro (M2)', 'MacBook Air (M1)', 'MacBook Pro Intel',
        ],
        'Acer' => ['Aspire 3', 'Aspire 5', 'Aspire 7', 'Swift Go', 'Swift X', 'Nitro V', 'Nitro 5', 'Predator Helios', 'Chromebook'],
        'ASUS' => ['VivoBook', 'Zenbook', 'ExpertBook', 'TUF Gaming', 'ROG Zephyrus', 'ROG Strix', 'ProArt Studiobook', 'Chromebook'],
        'Dell' => ['Inspiron', 'XPS', 'Latitude', 'Precision', 'Vostro', 'G Series', 'Alienware'],
        'HP' => ['HP Laptop', 'Pavilion', 'Envy', 'Spectre', 'Victus', 'OMEN', 'ProBook', 'EliteBook', 'ZBook', 'Chromebook'],
        'Lenovo' => ['IdeaPad', 'Yoga', 'ThinkPad', 'ThinkBook', 'LOQ', 'Legion', 'V Series', 'Chromebook'],
        'Microsoft' => ['Surface Laptop 7', 'Surface Laptop 6', 'Surface Laptop Studio 2', 'Surface Book', 'Surface Laptop Go'],
        'MSI' => ['Modern', 'Prestige', 'Creator', 'Thin', 'Katana', 'Cyborg', 'Stealth', 'Raider', 'Titan'],
        'Samsung' => ['Galaxy Book5 Pro', 'Galaxy Book5 360', 'Galaxy Book4', 'Galaxy Chromebook'],
        'Huawei' => ['MateBook X Pro', 'MateBook 14', 'MateBook D 16', 'MateBook D 14'],
        'LG' => ['LG gram Pro', 'LG gram', 'LG UltraPC'],
        'Razer' => ['Blade 14', 'Blade 15', 'Blade 16', 'Blade 18'],
        'Gateway' => ['Gateway Ultra Slim', 'Gateway Creator Series'],
    ],

    'Computadora de escritorio' => [
        'Apple' => ['iMac 24 pulgadas', 'Mac mini', 'Mac Studio', 'Mac Pro', 'iMac Intel', 'Mac mini Intel'],
        'Acer' => ['Aspire Desktop', 'Veriton', 'Predator Orion'],
        'ASUS' => ['ExpertCenter', 'ROG G Series', 'ProArt Station'],
        'Dell' => ['Inspiron Desktop', 'OptiPlex', 'Precision', 'Alienware Aurora'],
        'HP' => ['HP Desktop', 'Pavilion Desktop', 'ProDesk', 'EliteDesk', 'Z Workstation', 'OMEN Desktop'],
        'Lenovo' => ['IdeaCentre', 'ThinkCentre', 'Legion Tower', 'ThinkStation'],
        'MSI' => ['PRO DP', 'Cubi', 'MAG Infinite', 'MEG Aegis'],
        'Ensamblada' => ['PC básica', 'PC de oficina', 'PC gamer', 'Estación de trabajo', 'All-in-One'],
    ],

    'Reloj inteligente' => [
        'Apple' => ['Apple Watch Ultra 3', 'Apple Watch Series 11', 'Apple Watch SE 3', 'Apple Watch Ultra 2', 'Apple Watch Series 10', 'Apple Watch Series 9', 'Apple Watch SE 2'],
        'Samsung' => ['Galaxy Watch Ultra', 'Galaxy Watch8 Classic', 'Galaxy Watch8', 'Galaxy Watch7', 'Galaxy Watch6 Classic', 'Galaxy Watch6', 'Galaxy Fit3'],
        'Huawei' => ['HUAWEI WATCH 5', 'HUAWEI WATCH GT 5 Pro', 'HUAWEI WATCH GT 5', 'HUAWEI WATCH FIT 4 Pro', 'HUAWEI WATCH FIT 4', 'HUAWEI Band 10'],
        'Xiaomi' => ['Xiaomi Watch S4', 'Xiaomi Watch 2 Pro', 'Xiaomi Watch 2', 'Redmi Watch 5', 'Redmi Watch 4', 'Xiaomi Smart Band 9 Pro', 'Xiaomi Smart Band 9'],
        'Garmin' => ['fēnix 8', 'Venu 3', 'Forerunner 965', 'Forerunner 265', 'vívoactive 5', 'Instinct 3'],
        'Amazfit' => ['T-Rex 3 Pro', 'T-Rex 3', 'Balance 2', 'Balance', 'Active 2', 'Bip 6'],
        'Google' => ['Pixel Watch 4', 'Pixel Watch 3', 'Pixel Watch 2'],
        'Fitbit' => ['Sense 2', 'Versa 4', 'Charge 6', 'Inspire 3'],
    ],

    'Consola de videojuegos' => [
        'Sony PlayStation' => ['PlayStation 5 Pro', 'PlayStation 5 Slim', 'PlayStation 5', 'PlayStation 4 Pro', 'PlayStation 4 Slim', 'PlayStation 4', 'PlayStation 3'],
        'Microsoft Xbox' => ['Xbox Series X', 'Xbox Series S', 'Xbox One X', 'Xbox One S', 'Xbox One', 'Xbox 360'],
        'Nintendo' => ['Nintendo Switch 2', 'Nintendo Switch OLED', 'Nintendo Switch', 'Nintendo Switch Lite', 'Nintendo 3DS', 'Nintendo Wii U', 'Nintendo Wii'],
        'Valve' => ['Steam Deck OLED', 'Steam Deck LCD'],
        'ASUS' => ['ROG Ally X', 'ROG Ally'],
        'Lenovo' => ['Legion Go S', 'Legion Go'],
    ],

    'Televisión / Smart TV' => [
        'Samsung' => ['Neo QLED 8K', 'Neo QLED 4K', 'OLED', 'QLED', 'Crystal UHD', 'The Frame'],
        'LG' => ['OLED evo', 'OLED', 'QNED evo', 'QNED', 'NanoCell', 'UHD AI'],
        'Sony' => ['BRAVIA 9', 'BRAVIA 8', 'BRAVIA 7', 'BRAVIA 5', 'BRAVIA OLED', 'BRAVIA LED'],
        'TCL' => ['QM8K', 'QM7K', 'C8K', 'C7K', 'C6K', 'QLED', 'Google TV'],
        'Hisense' => ['UX', 'U9', 'U8', 'U7', 'U6', 'QLED', 'Roku TV'],
        'Philips' => ['OLED+', 'OLED', 'The One', 'Ambilight TV', 'Roku TV'],
        'Panasonic' => ['Z95', 'Z90', 'W95', 'W90', 'Viera'],
        'Vizio' => ['Quantum Pro', 'Quantum', 'V Series', 'D Series'],
        'Roku' => ['Roku Pro Series', 'Roku Plus Series', 'Roku Select Series'],
        'Sharp' => ['AQUOS XLED', 'AQUOS QLED', 'AQUOS LED'],
    ],

    'Monitor' => [
        'Samsung' => ['Odyssey OLED', 'Odyssey Neo G9', 'Odyssey G7', 'ViewFinity', 'Smart Monitor'],
        'LG' => ['UltraGear OLED', 'UltraGear', 'UltraFine', 'MyView Smart Monitor'],
        'Dell' => ['UltraSharp', 'P Series', 'S Series', 'Alienware'],
        'HP' => ['Series 5', 'Series 7 Pro', 'OMEN', 'E-Series'],
        'ASUS' => ['ProArt', 'ROG Swift', 'TUF Gaming', 'Eye Care'],
        'Acer' => ['Predator', 'Nitro', 'Vero', 'Essential'],
        'MSI' => ['MPG', 'MAG', 'G Series', 'Modern'],
        'BenQ' => ['MOBIUZ', 'ZOWIE', 'DesignVue', 'PhotoVue'],
        'AOC' => ['AGON PRO', 'AGON', 'Gaming', 'Essential'],
        'Lenovo' => ['Legion', 'ThinkVision', 'L Series'],
    ],

    'Audífonos' => [
        'Apple' => ['AirPods 4', 'AirPods 4 con ANC', 'AirPods Pro 3', 'AirPods Pro 2', 'AirPods Max'],
        'Samsung' => ['Galaxy Buds3 Pro', 'Galaxy Buds3', 'Galaxy Buds FE'],
        'Sony' => ['WH-1000XM6', 'WH-1000XM5', 'WF-1000XM5', 'ULT WEAR', 'LinkBuds'],
        'JBL' => ['Tour Pro', 'Live', 'Tune', 'Wave', 'Quantum'],
        'Bose' => ['QuietComfort Ultra Headphones', 'QuietComfort Headphones', 'QuietComfort Ultra Earbuds'],
        'Beats' => ['Studio Pro', 'Solo 4', 'Powerbeats Pro 2', 'Studio Buds+'],
        'Huawei' => ['FreeBuds Pro', 'FreeBuds', 'FreeClip'],
        'Xiaomi' => ['Buds Pro', 'Redmi Buds Pro', 'Redmi Buds'],
    ],

    'Bocina' => [
        'JBL' => ['Boombox', 'Xtreme', 'Charge', 'Flip', 'Go', 'PartyBox'],
        'Bose' => ['SoundLink Max', 'SoundLink Flex', 'Smart Speaker'],
        'Sony' => ['ULT FIELD', 'SRS-XG', 'SRS-XE', 'SRS-XB'],
        'Sonos' => ['Era 300', 'Era 100', 'Move 2', 'Roam 2'],
        'Apple' => ['HomePod', 'HomePod mini'],
        'Amazon' => ['Echo Studio', 'Echo', 'Echo Dot', 'Echo Pop'],
        'Google' => ['Nest Audio', 'Nest Mini', 'Nest Hub'],
        'Marshall' => ['Woburn', 'Stanmore', 'Acton', 'Emberton', 'Willen'],
    ],

    'Cámara' => [
        'Canon' => ['EOS R1', 'EOS R5 Mark II', 'EOS R6 Mark II', 'EOS R8', 'EOS R50', 'PowerShot'],
        'Nikon' => ['Z9', 'Z8', 'Z6 III', 'Z5', 'Z50 II', 'D Series'],
        'Sony' => ['Alpha 1', 'Alpha 9', 'Alpha 7', 'Alpha 6', 'ZV Series', 'Cyber-shot'],
        'Fujifilm' => ['GFX Series', 'X-H Series', 'X-T Series', 'X-S Series', 'X100 Series'],
        'Panasonic' => ['LUMIX S Series', 'LUMIX G Series', 'LUMIX TZ Series'],
        'GoPro' => ['HERO13 Black', 'HERO12 Black', 'HERO11 Black', 'MAX'],
        'DJI' => ['Osmo Action', 'Osmo Pocket', 'Ronin 4D'],
        'Insta360' => ['X Series', 'Ace Pro', 'GO Series', 'ONE RS'],
    ],

    'Impresora' => [
        'HP' => ['DeskJet', 'Smart Tank', 'OfficeJet Pro', 'LaserJet', 'DesignJet'],
        'Epson' => ['EcoTank', 'WorkForce', 'SureColor', 'L Series'],
        'Canon' => ['PIXMA', 'MAXIFY', 'imageCLASS', 'imagePROGRAF'],
        'Brother' => ['InkBenefit Tank', 'Laser', 'Business Smart', 'Label Printer'],
        'Xerox' => ['C Series', 'B Series', 'VersaLink', 'AltaLink'],
        'Ricoh' => ['M C Series', 'IM Series', 'P Series', 'SP Series'],
    ],

    'Router / módem' => [
        'TP-Link' => ['Archer', 'Deco', 'Omada', 'Aginet'],
        'Huawei' => ['WiFi AX', 'WiFi Mesh', 'Mobile WiFi', 'CPE 5G'],
        'ZTE' => ['CPE 5G', 'ZXHN', 'MF Series'],
        'Netgear' => ['Nighthawk', 'Orbi', 'Business'],
        'ASUS' => ['ROG Rapture', 'RT Series', 'ZenWiFi'],
        'Linksys' => ['Velop', 'Hydra Pro', 'MR Series'],
        'Mercusys' => ['Halo', 'MR Series', 'AC Series'],
        'Ubiquiti' => ['UniFi Dream', 'UniFi Cloud Gateway', 'AmpliFi'],
    ],

    'Terminal punto de venta' => [
        'Clip' => ['Clip Pro 2', 'Clip Total 2', 'Clip Stand', 'Clip Plus 2'],
        'Mercado Pago' => ['Point Smart 2', 'Point Air', 'Point Blue'],
        'Zettle' => ['Zettle Terminal', 'Zettle Reader 2'],
        'Square' => ['Square Terminal', 'Square Register', 'Square Reader'],
        'Verifone' => ['T650p', 'P400', 'V240m', 'V400m'],
        'Ingenico' => ['Axium DX8000', 'Move/5000', 'Desk/5000', 'Lane/3000'],
    ],

    'Proyector' => [
        'Epson' => ['EpiqVision', 'PowerLite', 'Pro Cinema', 'Home Cinema'],
        'BenQ' => ['CinePrime', 'CineHome', 'Gaming', 'Business'],
        'ViewSonic' => ['X Series', 'M Series', 'LS Series', 'PA Series'],
        'Optoma' => ['UHD Series', 'CinemaX', 'GT Series', 'ZH Series'],
        'LG' => ['CineBeam', 'ProBeam'],
        'Samsung' => ['The Premiere', 'The Freestyle'],
    ],
];
