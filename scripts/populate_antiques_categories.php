<?php
// populate_antiques_categories.php - script to set up antiques & militaria category structure and filters.
// Run from CLI: php scripts/populate_antiques_categories.php

require_once __DIR__ . '/../config/config.php';

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

try {
    echo "Starting categories and filters migration...\n";
    // 1. Clear old categories, subcategories, options, option categories, types
    echo "Clearing old tables...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("TRUNCATE TABLE `" . _DB_PREFIX_ . "category`");
    $db->exec("TRUNCATE TABLE `" . _DB_PREFIX_ . "subcategory`");
    $db->exec("DELETE FROM `" . _DB_PREFIX_ . "option` WHERE id > 1"); // Keep ID 1 (price)
    $db->exec("TRUNCATE TABLE `" . _DB_PREFIX_ . "option_category`");
    $db->exec("TRUNCATE TABLE `" . _DB_PREFIX_ . "type`");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Reset auto-increment
    $db->exec("ALTER TABLE `" . _DB_PREFIX_ . "category` AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE `" . _DB_PREFIX_ . "type` AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE `" . _DB_PREFIX_ . "option` AUTO_INCREMENT = 2");

    // 2. Define category structure
    $categories = [
        [
            'name' => 'Militaria do 1945',
            'slug' => 'militaria-do-1945',
            'keywords' => 'militaria, do 1945, broń, mundury, wojsko, retro, historyczne, rycerstwo, odznaczenia',
            'description' => 'Militaria historyczne do 1945 roku - broń, dokumenty, guziki wojskowe, naszywki, odznaczenia, odznaki, rycerstwo, umundurowanie i wyposażenie.',
            'subcategories' => [
                ['name' => 'Broń', 'slug' => 'bron'],
                ['name' => 'Dokumenty', 'slug' => 'dokumenty'],
                ['name' => 'Guziki wojskowe', 'slug' => 'guziki-wojskowe'],
                ['name' => 'Naszywki', 'slug' => 'naszywki'],
                ['name' => 'Odznaczenia', 'slug' => 'odznaczenia'],
                ['name' => 'Odznaki', 'slug' => 'odznaki'],
                ['name' => 'Rycerstwo', 'slug' => 'rycerstwo'],
                ['name' => 'Umundurowanie i Wyposażenie', 'slug' => 'umundurowanie-i-wyposazenie'],
                ['name' => 'Zdjęcia', 'slug' => 'zdjecia'],
                ['name' => 'Literatura', 'slug' => 'literatura'],
                ['name' => 'Pozostałe', 'slug' => 'pozostale']
            ]
        ],
        [
            'name' => 'Militaria współczesne',
            'slug' => 'militaria-wspolczesne',
            'keywords' => 'militaria, współczesne, asg, taktyczne, mundury, wot, paramilitarne',
            'description' => 'Militaria współczesne po 1945 roku - umundurowanie, wyposażenie, paramilitarne, WOT.',
            'subcategories' => [
                ['name' => 'Broń współczesna', 'slug' => 'bron-wspolczesna'],
                ['name' => 'Dokumenty', 'slug' => 'dokumenty-wspolczesne'],
                ['name' => 'Naszywki', 'slug' => 'naszywki-wspolczesne'],
                ['name' => 'Odznaczenia i Odznaki', 'slug' => 'odznaczenia-i-odznaki-wspolczesne'],
                ['name' => 'Paramilitarne', 'slug' => 'paramilitarne'],
                ['name' => 'Umundurowanie i Wyposażenie', 'slug' => 'umundurowanie-i-wyposazenie-wspolczesne'],
                ['name' => 'Wojska Obrony Terytorialnej', 'slug' => 'wojska-obrony-terytorialnej'],
                ['name' => 'Literatura', 'slug' => 'literatura-wspolczesna'],
                ['name' => 'Zdjęcia', 'slug' => 'zdjecia-wspolczesne'],
                ['name' => 'Pozostałe', 'slug' => 'pozostale-wspolczesne']
            ]
        ],
        [
            'name' => 'Design i Antyki',
            'slug' => 'design-i-antyki',
            'keywords' => 'antyki, design, meble antyczne, lampy, oświetlenie, zegary retro, porcelana, ceramika, szkło, platery',
            'description' => 'Antyczne meble, zegary, oświetlenie, wyroby z porcelany, ceramiki, szkła, srebra oraz platery.',
            'subcategories' => [
                ['name' => 'Meble antyczne', 'slug' => 'meble-antyczne'],
                ['name' => 'Lampy i oświetlenie', 'slug' => 'lampy-i-oswietlenie'],
                ['name' => 'Zegary', 'slug' => 'zegary'],
                ['name' => 'Porcelana, ceramika i szkło', 'slug' => 'porcelana-ceramika-szklo'],
                ['name' => 'Platery, srebra i metaloplastyka', 'slug' => 'platery-srebra-metaloplastyka'],
                ['name' => 'Przedmioty codziennego użytku (retro)', 'slug' => 'przedmioty-codzienne-retro'],
                ['name' => 'Pozostałe', 'slug' => 'pozostale-antyki']
            ]
        ],
        [
            'name' => 'Sztuka i Rękodzieło',
            'slug' => 'sztuka-i-rekodzielo',
            'keywords' => 'sztuka, malarstwo, obrazy, rzeźby, grafika, plakaty, biżuteria dawna, rękodzieło',
            'description' => 'Obrazy, malarstwo, rzeźby, grafiki, dawna biżuteria i unikalne rękodzieło artystyczne.',
            'subcategories' => [
                ['name' => 'Malarstwo i obrazy', 'slug' => 'malarstwo-i-obrazy'],
                ['name' => 'Rzeźby i płaskorzeźby', 'slug' => 'rzezby-i-plaskorzezy'],
                ['name' => 'Grafika, rysunek i plakaty', 'slug' => 'grafika-rysunek-plakaty'],
                ['name' => 'Biżuteria dawna i artystyczna', 'slug' => 'bizuteria-dawna-artystyczna'],
                ['name' => 'Rękodzieło i wyroby ludowe', 'slug' => 'rekodzielo-wyroby-ludowe'],
                ['name' => 'Pozostałe', 'slug' => 'pozostale-sztuka']
            ]
        ],
        [
            'name' => 'Kolekcje (Hobby)',
            'slug' => 'kolekcje-hobby',
            'keywords' => 'kolekcje, hobby, filatelistyka, birofilistyka, modelarstwo, pamiątki prl, karty telefoniczne, trafika',
            'description' => 'Zbiory kolekcjonerskie, filatelistyka, birofilistyka, modelarstwo, pamiątki PRL, skamieliny, trafika i inne hobby.',
            'subcategories' => [
                ['name' => 'Akcesoria alkoholowe', 'slug' => 'akcesoria-alkoholowe'],
                ['name' => 'Birofilistyka', 'slug' => 'birofilistyka'],
                ['name' => 'Filatelistyka', 'slug' => 'filatelistyka'],
                ['name' => 'Flagi i symbole narodowe', 'slug' => 'flagi-i-symbole-narodowe'],
                ['name' => 'Karty telefoniczne', 'slug' => 'karty-telefoniczne'],
                ['name' => 'Modelarstwo', 'slug' => 'modelarstwo'],
                ['name' => 'Pamiątki PRL-u', 'slug' => 'pamiatki-prl-u'],
                ['name' => 'Skamieliny, minerały i muszle', 'slug' => 'skamieliny-mineraly-muszle'],
                ['name' => 'Trafika', 'slug' => 'trafika'],
                ['name' => 'Pozostałe / Inne kolekcje', 'slug' => 'inne-kolekcje']
            ]
        ],
        [
            'name' => 'Numizmatyka i Falerystyka',
            'slug' => 'numizmatyka-i-falerystyka',
            'keywords' => 'numizmatyka, monety, banknoty, falerystyka, odznaki, kolekcja',
            'description' => 'Monety polskie i zagraniczne, banknoty, odznaki, ordery i falerystyka.',
            'subcategories' => [
                ['name' => 'Monety polskie', 'slug' => 'monety-polskie'],
                ['name' => 'Monety zagraniczne', 'slug' => 'monety-zagraniczne'],
                ['name' => 'Banknoty i papiery wartościowe', 'slug' => 'banknoty-i-papiery'],
                ['name' => 'Odznaki i przypinki', 'slug' => 'odznaki-i-przypinki']
            ]
        ],
        [
            'name' => 'Starodruki i Dokumenty',
            'slug' => 'starodruki-i-dokumenty',
            'keywords' => 'starodruki, dokumenty, listy, mapy, stare książki, pocztówki',
            'description' => 'Stare dokumenty, listy, rękopisy, mapy historyczne, pocztówki i starodruki.',
            'subcategories' => [
                ['name' => 'Książki i czasopisma retro', 'slug' => 'ksiazki-i-czasopisma-retro'],
                ['name' => 'Listy, mapy i dokumenty historyczne', 'slug' => 'listy-mapy-dokumenty'],
                ['name' => 'Pocztówki i fotografie vintage', 'slug' => 'pocztowki-i-fotografie']
            ]
        ],
        [
            'name' => 'Wykrywacz metalu / Znaleziska',
            'slug' => 'znaleziska-wykrywacz',
            'keywords' => 'wykrywacz metalu, znaleziska, artefakty, poszukiwacze, detektor',
            'description' => 'Artefakty i przedmioty odnalezione za pomocą detektorów metali oraz akcesoria dla poszukiwaczy.',
            'subcategories' => [
                ['name' => 'Artefakty ziemne', 'slug' => 'artefakty-ziemne'],
                ['name' => 'Elementy maszyn i pojazdów', 'slug' => 'elementy-maszyn-i-pojazdow'],
                ['name' => 'Akcesoria poszukiwawcze', 'slug' => 'akcesoria-poszukiwawcze']
            ]
        ]
    ];

    // Insert categories
    $insertCatSth = $db->prepare("
        INSERT INTO `" . _DB_PREFIX_ . "category` (
            category_id, position, slug, name, path, keywords, description, h1, title
        ) VALUES (
            :category_id, :position, :slug, :name, :path, :keywords, :description, :h1, :title
        )
    ");

    $insertSubSth = $db->prepare("
        INSERT INTO `" . _DB_PREFIX_ . "subcategory` (category_id, subcategory_id)
        VALUES (:category_id, :subcategory_id)
    ");

    $mainPosition = 1;
    foreach ($categories as $catData) {
        $slug = $catData['slug'];
        $name = $catData['name'];
        echo "Inserting category: $name...\n";
        
        $insertCatSth->execute([
            ':category_id' => 0,
            ':position' => $mainPosition++,
            ':slug' => $slug,
            ':name' => $name,
            ':path' => $slug,
            ':keywords' => $catData['keywords'],
            ':description' => $catData['description'],
            ':h1' => $name,
            ':title' => $name . " - Ogłoszenia i giełda w Polsce"
        ]);
        
        $parent_id = $db->lastInsertId();
        
        $subPosition = 1;
        foreach ($catData['subcategories'] as $subData) {
            $subSlug = $slug . '/' . $subData['slug'];
            $subName = $subData['name'];
            
            $insertCatSth->execute([
                ':category_id' => $parent_id,
                ':position' => $subPosition++,
                ':slug' => $subData['slug'],
                ':name' => $subName,
                ':path' => $subSlug,
                ':keywords' => $catData['keywords'] . ", " . strtolower($subName),
                ':description' => $subName . " - ogłoszenia lokalne i wymiana w Polsce.",
                ':h1' => $subName,
                ':title' => $subName . " - Kupię, sprzedam, zamienię"
            ]);
            
            $subcategory_id = $db->lastInsertId();
            
            // Link in subcategory mapping
            $insertSubSth->execute([
                ':category_id' => $parent_id,
                ':subcategory_id' => $subcategory_id
            ]);
        }
    }

    // 3. Define transaction types (Tabela type)
    echo "Inserting transaction types...\n";
    $insertTypeSth = $db->prepare("INSERT INTO `" . _DB_PREFIX_ . "type` (slug, name) VALUES (:slug, :name)");
    $types = [
        ['slug' => 'sprzedaz', 'name' => 'Sprzedaż'],
        ['slug' => 'wymiana', 'name' => 'Wymiana'],
        ['slug' => 'zamiana', 'name' => 'Zamiana']
    ];
    foreach ($types as $t) {
        $insertTypeSth->execute([':slug' => $t['slug'], ':name' => $t['name']]);
    }

    // 4. Define and insert custom filters (Tabela option)
    echo "Inserting custom filters/options...\n";
    $insertOptionSth = $db->prepare("
        INSERT INTO `" . _DB_PREFIX_ . "option` (
            name, slug, position, kind, required, categories_all, search, select_choices, pernament
        ) VALUES (
            :name, :slug, :position, :kind, :required, :categories_all, :search, :select_choices, :pernament
        )
    ");

    $options = [
        [
            'name' => 'Stan zachowania',
            'slug' => 'stan',
            'position' => 1,
            'kind' => 'select',
            'required' => 1,
            'categories_all' => 1,
            'search' => 1,
            'select_choices' => 'Nowy; Używany; Uszkodzony; Do renowacji',
            'pernament' => 0
        ],
        [
            'name' => 'Epoka / Okres',
            'slug' => 'epoka',
            'position' => 2,
            'kind' => 'select',
            'required' => 0,
            'categories_all' => 1,
            'search' => 1,
            'select_choices' => 'Starożytność; Średniowiecze; Nowożytność (XVI-XVIII w.); XIX wiek; I Wojna Światowa (1914-1918); Dwudziestolecie międzywojenne; II Wojna Światowa (1939-1945); PRL / Zimna Wojna; Współczesność (po 1989)',
            'pernament' => 0
        ],
        [
            'name' => 'Oryginalność',
            'slug' => 'oryginalnosc',
            'position' => 3,
            'kind' => 'select',
            'required' => 0,
            'categories_all' => 1,
            'search' => 1,
            'select_choices' => 'Oryginał; Kopia; Replika; Licencyjny; Rekonstrukcja',
            'pernament' => 0
        ],
        [
            'name' => 'Pochodzenie',
            'slug' => 'pochodzenie',
            'position' => 4,
            'kind' => 'select',
            'required' => 0,
            'categories_all' => 1,
            'search' => 1,
            'select_choices' => 'Polska; Niemcy; ZSRR; USA; Inne państwo; Nieokreślone',
            'pernament' => 0
        ]
    ];

    foreach ($options as $opt) {
        $insertOptionSth->execute([
            ':name' => $opt['name'],
            ':slug' => $opt['slug'],
            ':position' => $opt['position'],
            ':kind' => $opt['kind'],
            ':required' => $opt['required'],
            ':categories_all' => $opt['categories_all'],
            ':search' => $opt['search'],
            ':select_choices' => $opt['select_choices'],
            ':pernament' => $opt['pernament']
        ]);
    }

    echo "Categories and filters migration completed successfully!\n";

} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
