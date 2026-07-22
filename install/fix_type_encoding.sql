-- Naprawa błędnego kodowania UTF-8 w tabeli 'type'
-- Uruchom na serwerze produkcyjnym: mysql -u USER -p DATABASE < fix_type_encoding.sql

-- Sprawdź aktualne wartości
SELECT id, name, slug FROM `type`;

-- Napraw kodowanie (Latin1 błędnie odczytany jako UTF-8)
UPDATE `type` SET `name` = CONVERT(CAST(CONVERT(`name` USING latin1) AS BINARY) USING utf8mb4) WHERE `name` LIKE '%Ã%';

-- Jeśli powyższe nie zadziała, użyj bezpośrednich wartości:
-- UPDATE `type` SET `name` = 'Szukam podwykonawców (Zlecenia)' WHERE `slug` = 'kupie';
-- UPDATE `type` SET `name` = 'Szukam zleceń (Oferty usług)' WHERE `slug` = 'uslugi';
-- UPDATE `type` SET `name` = 'Wynajmę' WHERE `slug` = 'wynajme';
-- UPDATE `type` SET `name` = 'Zamienię' WHERE `slug` = 'zamienie';

-- Sprawdź wynik
SELECT id, name, slug FROM `type`;
