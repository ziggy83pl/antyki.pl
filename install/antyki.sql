-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Czas generowania: 21 Kwi 2017, 10:28
-- Wersja serwera: 5.7.14
-- Wersja PHP: 7.0.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Baza danych: `gielda-budowlana`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `admin`
--

CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `admin_logs`
--

CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `logged` tinyint(1) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `admin_session`
--

CREATE TABLE IF NOT EXISTS `admin_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `code` varchar(64) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `article`
--

CREATE TABLE IF NOT EXISTS `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `thumb` varchar(256) NOT NULL,
  `content` mediumtext NOT NULL,
  `content_short` varchar(512) NOT NULL,
  `keywords` varchar(512) NOT NULL,
  `description` varchar(512) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `category`
--

CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `thumb` varchar(256) DEFAULT NULL,
  `path` text,
  `content` text,
  `h1` varchar(512) DEFAULT NULL,
  `title` varchar(512) DEFAULT NULL,
  `keywords` varchar(512) DEFAULT NULL,
  `description` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `clipboard`
--

CREATE TABLE IF NOT EXISTS `clipboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `info`
--

CREATE TABLE IF NOT EXISTS `info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `page` varchar(32) DEFAULT NULL,
  `content` mediumtext NOT NULL,
  `keywords` varchar(512) NOT NULL,
  `description` varchar(512) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Zrzut danych tabeli `info`
--

INSERT INTO `info` (`id`, `position`, `name`, `slug`, `page`, `content`, `keywords`, `description`) VALUES
(1, 2, 'Polityka prywatności', 'polityka-prywatnosci', 'privacy_policy', '<h3>Polityka Prywatności i RODO</h3>\r\n<p>Niniejsza Polityka Prywatności określa zasady przetwarzania i ochrony danych osobowych użytkowników korzystających z serwisu Giełda Antyków i Militarii.</p>\r\n\r\n<h4>1. Administrator Danych Osobowych</h4>\r\n<p>Administratorem danych osobowych zbieranych za pośrednictwem serwisu jest właściciel serwisu (zwany dalej Administratorem).</p>\r\n\r\n<h4>2. Cele i Podstawy Prawne Przetwarzania Danych</h4>\r\n<p>Dane osobowe przetwarzane są zgodnie z Rozporządzeniem Parlamentu Europejskiego i Rady (UE) 2016/679 z dnia 27 kwietnia 2016 r. (RODO):</p>\r\n<ul>\r\n  <li><strong>Rejestracja i obsługa konta użytkownika</strong> (art. 6 ust. 1 lit. b RODO) – w celu realizacji umowy o świadczenie usług drogą elektroniczną.</li>\r\n  <li><strong>Publikacja ogłoszeń</strong> (art. 6 ust. 1 lit. b RODO) – w celu świadczenia usługi zamieszczania ogłoszeń w serwisie.</li>\r\n  <li><strong>Kontakt z użytkownikiem</strong> (art. 6 ust. 1 lit. f RODO) – uzasadniony interes administratora polegający na obsłudze zapytań i komunikacji.</li>\r\n  <li><strong>Cele marketingowe i analityczne</strong> (art. 6 ust. 1 lit. a RODO) – na podstawie dobrowolnej zgody użytkownika (np. pliki cookies).</li>\r\n</ul>\r\n\r\n<h4>3. Prawa Użytkownika</h4>\r\n<p>Każdemu użytkownikowi przysługuje prawo do:</p>\r\n<ul>\r\n  <li>Dostępu do swoich danych osobowych oraz otrzymania ich kopii.</li>\r\n  <li>Sprostowania (poprawiania) swoich danych.</li>\r\n  <li>Usunięcia danych (&quot;prawo do bycia zapomnianym&quot;).</li>\r\n  <li>Ograniczenia przetwarzania danych osobowych.</li>\r\n  <li>Przenoszenia danych.</li>\r\n  <li>Wniesienia sprzeciwu wobec przetwarzania.</li>\r\n  <li>Cofnięcia zgody w dowolnym momencie.</li>\r\n  <li>Wniesienia skargi do Prezesa Urzędu Ochrony Danych Osobowych (UODO).</li>\r\n</ul>', '', ''),
(2, 3, 'Regulamin', 'regulamin', 'rules', '<h3>Regulamin Giełdy Antyków, Militariów i Znalezisk</h3>\r\n\r\n<h4>1. Postanowienia Ogólne</h4>\r\n<p>Portal działa wyłącznie jako platforma ogłoszeniowa typu C2C (Customer-to-Customer). Użytkownicy publikują ogłoszenia i dokonują transakcji bezpośrednio między sobą. Serwis NIE pośredniczy w płatnościach, wysyłkach ani nie prowadzi własnej sprzedaży lub skupu przedmiotów.</p>\r\n\r\n<h4>2. Odpowiedzialność za Przedmioty i Transakcje</h4>\r\n<p>Użytkownicy ponoszą pełną odpowiedzialność prawną i finansową za oferowane przedmioty (w tym za ich oryginalność, legalność pochodzenia) oraz za realizację transakcji. Zabrania się oferowania przedmiotów niesprawnych/niebezpiecznych bez odpowiedniego opisu (np. broń bez certyfikatów deaktywacji/kolekcjonerskich).</p>\r\n\r\n<h4>3. Warunki Korzystania</h4>\r\n<p>Dodawanie ogłoszeń jest darmowe i przeznaczone dla osób pełnoletnich z terenu Polski. Wszelkie spory między stronami transakcji użytkownicy rozwiązują we własnym zakresie.</p>\r\n<p><span style="color:#696969;">Ostatnia aktualizacja regulaminu: 2026-07-20</span></p>', '', ''),
(3, 1, 'Kontakt', 'kontakt', 'contact', '', '', '');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `logs_mail`
--

CREATE TABLE IF NOT EXISTS `logs_mail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receiver` varchar(64) NOT NULL,
  `action` varchar(32) NOT NULL,
  `content` text NOT NULL,
  `ip` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `logs_offer`
--

CREATE TABLE IF NOT EXISTS `logs_offer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `logs_user`
--

CREATE TABLE IF NOT EXISTS `logs_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `mails`
--

CREATE TABLE IF NOT EXISTS `mails` (
  `name` varchar(64) NOT NULL,
  `full_name` varchar(64) NOT NULL,
  `subject` text NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Zrzut danych tabeli `mails`
--

INSERT INTO `mails` (`name`, `full_name`, `subject`, `message`) VALUES
('contact_form', 'Contact form', 'Wiadomość z formularza kontaktowego strony {title}', '<p>Witaj!</p>\r\n\r\n<p>Została do Ciebie wysłana wiadomość z formularza kontaktowego ze strony {base_url}</p>\r\n\r\n<p>Nadawca: {name}</p>\r\n\r\n<p>Adres email: {email}</p>\r\n\r\n<p>Wiadomość: {message}</p>\r\n'),
('finish_promote', 'Finish promote', 'Zakończenie promowania ogłoszenia {offer_name}', '<p>Witaj,</p>\r\n\r\n<p>Twoje ogłoszenie&nbsp;<a href=\"{offer_url}\">{offer_url}</a>&nbsp;na stronie&nbsp;<a href=\"{base_url}\">{base_url}</a>&nbsp;przestało&nbsp;być promowane.</p>\r\n\r\n<p>Wyr&oacute;żnij się na tle konkurencji i ponownie wypromuj swoje ogłoszenie!</p>\r\n\r\n<p>Więcej szczeg&oacute;ł&oacute;w na stronie&nbsp;<a href=\"{offer_url}\">{offer_url}</a>&nbsp;w zakładce &quot;Promuj&quot;</p>\r\n\r\n<p>Pozdrawiamy<br />\r\n{title}<br />\r\n<br />\r\n<a href=\"{base_url}\">{link_logo}</a></p>\r\n\r\n<p>&nbsp;</p>\r\n'),
('offer', 'Offer', 'Wiadomość do ogłoszenia {offer_name}', '<p>Witaj!</p>\r\n\r\n<p>Została do Ciebie wysłana wiadomość ze strony <a href=\"{base_url}\">{base_url}</a> dotycząca ogłoszenia&nbsp;<a href=\"{offer_url}\">{offer_url}</a></p>\r\n\r\n<p>Nadawca: {name}</p>\r\n\r\n<p>Adres email: {email}</p>\r\n\r\n<p>Wiadomość: {message}</p>\r\n'),
('offer_start', 'Offer - start displaying', 'Aktywacja ogłoszenia {offer_name}', '<p>Witaj!</p>\r\n\r\n<p>Dodałeś ogłoszenie&nbsp;<a href=\"{offer_url}\">{offer_url}</a>&nbsp;na stronie&nbsp;<a href=\"{base_url}\">{base_url}</a>.</p>\r\n\r\n<p>Dziękujemy za zainteresowanie naszym serwisem</p>\r\n\r\n<p>Pozdrawiamy<br />\r\n{title}<br />\r\n<br />\r\n<a href=\"{base_url}\">{link_logo}</a></p>\r\n'),
('offer_start_not_logged', 'Offer - start displaying (not logged)', 'Aktywacja ogłoszenia {offer_name}', '<p>Witaj!</p>\r\n\r\n<p>Aby aktywować ogłoszenie&nbsp;{offer_name} kliknij w link:&nbsp;<a href=\"{offer_activate_link}\">{offer_activate_link}</a>&nbsp;</p>\r\n\r\n<p>Link do edycji ogłoszenia:&nbsp;<a href=\"{offer_edit_link}\">{offer_edit_link}</a>&nbsp;</p>\r\n\r\n<p>Pozdrawiamy<br />\r\n{title}<br />\r\n<br />\r\n<a href=\"{base_url}\">{link_logo}</a></p>\r\n'),
('offers_finish', 'Offers - finish displaying', 'Zakończenie wyświetlania ogłoszenia w serwisie {title}', '<p>Witaj!</p>\r\n\r\n<p>Twoje ogłoszenia&nbsp;przestały&nbsp;być aktywne w serwisie &nbsp;<a href=\"{base_url}\">{base_url}</a>&nbsp;w dniu {date}:</p>\r\n\r\n<p><b>{offers_list}</b></p>\r\n\r\n<p>Aby je ponownie aktywować zaloguj się na swoje konto:&nbsp;<a href=\"{base_url}/moje_ogloszenia\">{base_url}/moje_ogloszenia</a></p>\r\n\r\n<p>Dziękujemy za zainteresowanie naszym serwisem</p>\r\n\r\n<p><br />\r\nPozdrawiamy<br />\r\n{title}</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>{link_logo}</p>\r\n'),
('offers_finish_not_logged', 'Offers - finish displaying (not logged)', 'Zakończenie wyświetlania ogłoszenia w serwisie {title}', '<p>Witaj!</p>\r\n\r\n<p>Twoje ogłoszenia&nbsp;przestały&nbsp;być aktywne w serwisie &nbsp;<a href=\"{base_url}\">{base_url}</a>&nbsp;w dniu {date}:</p>\r\n\r\n<p><b>{offers_list}</b></p>\r\n\r\n<p>Dziękujemy za zainteresowanie naszym serwisem</p>\r\n\r\n<p><br />\r\nPozdrawiamy<br />\r\n{title}</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>{link_logo}</p>\r\n'),
('profile', 'Profile', 'Wiadomość do profilu {username}', '<p>Witaj!</p>\r\n\r\n<p>Została do Ciebie wysłana wiadomość ze strony&nbsp;<a href=\"{base_url}\">{base_url}</a>&nbsp;ze strony Twojego profilu {username}</p>\r\n\r\n<p>Nadawca: {name}</p>\r\n\r\n<p>Adres email: {email}</p>\r\n\r\n<p>Wiadomość: {message}</p>\r\n'),
('register', 'Register', 'Witamy na stronie {title}', '<p>Witaj na stronie <a href=\"{base_url}\">{title}</a>!<br />\r\n<br />\r\nDziękujemy za rejestrację.<br />\r\n<br />\r\nŻeby ją dokończyć kliknij w link: <a href=\"{activation_link}\">{activation_link}</a><br />\r\n<br />\r\nInformujemy że link aktywacyjny jest ważny 24 godziny, po tym czasie nieaktywowane konta zostają usuwane.<br />\r\nJeśli to nie Ty się rejestrowałeś to zignoruj tą wiadomość<br />\r\n<br />\r\nPozdrawiamy<br />\r\n{title}<br />\r\n<br />\r\n<a href=\"{base_url}\">{link_logo}</a></p>\r\n'),
('register_fb', 'Register by Facebook', 'Witamy na stronie {title}', '<p>Witaj na stronie <a href=\"{base_url}\">{title}</a>!<br />\r\n<br />\r\nDziękujemy za rejestrację poprzez konto Facebook.</p>\r\n\r\n<p>Twoje losowo wygenerowane hasło to: {password}<br />\r\n<br />\r\nPozdrawiamy<br />\r\n{title}<br />\r\n<br />\r\n<a href=\"{base_url}\">{link_logo}</a></p>\r\n'),
('register_google', 'Register by Google', 'Witamy na stronie {title}', '<p>Witaj na stronie <a href=\"{base_url}\">{title}</a>!<br />\r\n<br />\r\nDziękujemy za rejestrację poprzez konto Google.</p>\r\n\r\n<p>Twoje losowo wygenerowane hasło to: {password}<br />\r\n<br />\r\nPozdrawiamy<br />\r\n{title}<br />\r\n<br />\r\n<a href=\"{base_url}\">{link_logo}</a></p>\r\n'),
('reset_password', 'Reset password', 'Reset hasła - {title}', '<p>Witaj {username}!<br />\r\n<br />\r\nAby zresetować swoje hasło do serwisu <a href=\"{base_url}\">{title}</a> kliknij w następujący link: <a href=\"{reset_password_link}\">{reset_password_link}</a><br />\r\n<br />\r\nPozdrawiamy<br />\r\n{title}</p>\r\n'),
('start_promote', 'Start promote', 'Rozpoczęcie promowania ogłoszenia {offer_name} ', '<p>Witaj!&nbsp;</p>\r\n\r\n<p>Twoje ogłoszenie&nbsp;<a href=\"{offer_url}\">{offer_url}</a>&nbsp;na stronie&nbsp;<a href=\"{base_url}\">{base_url}</a>&nbsp;zaczęło&nbsp;być promowane.</p>\r\n\r\n<p>Dzięki temu będzie się wyr&oacute;żniać na tle konkurencji!</p>\r\n\r\n<p>Pozdrawiamy<br />\r\n{title}<br />\r\n<br />\r\n<a href=\"{base_url}\">{link_logo}</a></p>\r\n');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `mails_queue`
--

CREATE TABLE IF NOT EXISTS `mails_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receiver` varchar(64) NOT NULL,
  `action` varchar(32) NOT NULL,
  `data` text NOT NULL,
  `priority` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `offer`
--

CREATE TABLE IF NOT EXISTS `offer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(512) NOT NULL,
  `slug` varchar(512) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `price_negotiate` tinyint(1) DEFAULT NULL,
  `price_free` tinyint(1) DEFAULT NULL,
  `address` varchar(512) DEFAULT NULL,
  `address_lat` decimal(10,6) DEFAULT NULL,
  `address_long` decimal(10,6) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `state2_id` int(11) DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `description` mediumtext,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `admin_confirmed` tinyint(1) DEFAULT NULL,
  `promoted` tinyint(1) NOT NULL DEFAULT '0',
  `promoted_date_start` datetime DEFAULT NULL,
  `promoted_date_end` date DEFAULT NULL,
  `code` varchar(64) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `date_start` datetime NOT NULL,
  `days` int(11) NOT NULL,
  `date_finish` datetime NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `offer_days`
--

CREATE TABLE IF NOT EXISTS `offer_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `length` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `offer_days` (`id`, `length`, `cost`) VALUES
(1, 7, 0.00),
(2, 14, 0.00),
(3, 30, 0.00),
(4, 90, 0.00);


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `opinion`
--

CREATE TABLE IF NOT EXISTS `opinion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `fk_opinion_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_opinion_author` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `option`
--

CREATE TABLE IF NOT EXISTS `option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `slug` varchar(128) NOT NULL,
  `position` int(11) NOT NULL,
  `kind` varchar(16) DEFAULT NULL,
  `required` tinyint(1) DEFAULT NULL,
  `categories_all` int(11) DEFAULT NULL,
  `search` tinyint(1) DEFAULT NULL,
  `select_choices` text,
  `pernament` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Zrzut danych tabeli `options`
--

INSERT INTO `option` (`id`, `name`, `slug`, `position`, `kind`, `required`, `categories_all`, `search`, `select_choices`, `pernament`) VALUES
(1, 'Price', 'price', 0, 'price', 0, 1, 1, '', 1);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `option_category`
--

CREATE TABLE IF NOT EXISTS `option_category` (
  `option_id` int(11) NOT NULL,
  `option_category` int(11) NOT NULL,
  KEY `option_id` (`option_id`),
  KEY `option_category` (`option_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `option_value`
--

CREATE TABLE IF NOT EXISTS `option_value` (
  `offer_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `value` text NOT NULL,
  KEY `offer_id` (`offer_id`),
  KEY `option_id` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `payment`
--

CREATE TABLE IF NOT EXISTS `payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company` varchar(16) NOT NULL,
  `amount` decimal(7,2) NOT NULL,
  `status` varchar(16) NOT NULL,
  `item_id` int(11) NOT NULL,
  `type` varchar(16) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `payment_dotpay`
--

CREATE TABLE IF NOT EXISTS `payment_dotpay` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dotpay_id` varchar(7) NOT NULL,
  `operation_status` varchar(10) NOT NULL,
  `operation_number` varchar(15) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `operation_amount` varchar(10) NOT NULL,
  `email` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `payment_p24`
--

CREATE TABLE IF NOT EXISTS `payment_p24` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` varchar(32) NOT NULL,
  `p24_order_id` int(11) DEFAULT NULL,
  `status` varchar(16) DEFAULT NULL,
  `amount` varchar(10) DEFAULT NULL,
  `sandbox` tinyint(1) NOT NULL,
  `errors` text,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `payment_paypal`
--

CREATE TABLE IF NOT EXISTS `payment_paypal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `txnid` varchar(20) NOT NULL,
  `amount` decimal(7,2) NOT NULL,
  `status` varchar(25) NOT NULL,
  `email` varchar(64) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `photo`
--

CREATE TABLE IF NOT EXISTS `photo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `offer_id` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `folder` varchar(16) NOT NULL,
  `thumb` varchar(256) NOT NULL,
  `url` varchar(256) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `offer_id` (`offer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `reset_password`
--

CREATE TABLE IF NOT EXISTS `reset_password` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `used` tinyint(1) DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  `code` varchar(64) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `session_offer`
--

CREATE TABLE IF NOT EXISTS `session_offer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(64) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `session_user`
--

CREATE TABLE IF NOT EXISTS `session_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `code` varchar(64) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `name` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Zrzut danych tabeli `settings`
--

INSERT INTO `settings` (`name`, `value`) VALUES
('add_cost', '0'),
('add_offers_not_logged', '0'),
('ads_1', ''),
('ads_2', ''),
('ads_3', ''),
('ads_4', ''),
('ads_side_1', ''),
('ads_side_2', ''),
('allow_comments_fb_article', '1'),
('allow_comments_fb_profile', '1'),
('allow_refresh_offer','1'),
('analytics', ''),
('automatically_activate_offers', '1'),
('base_url', ''),
('black_list_email', ''),
('black_list_ip', ''),
('black_list_words','kurwa, chuj, pizda, pierdolić, pierdolony, jebać, jebany, cipa, kurwy, chuje, kurew, skurwiel, pożyczki bez bik, kredyt bez bik, chwilówki, pożyczka prywatna, bitcoin, crypto, forex, kasyno, casino, viagra, cialis, kamagra, narkotyki, amfetamina, haker, escort, masaż erotyczny, sex, anonse towarzyskie, szybki zysk, zarabiaj w domu, praca online, xanax, alprazolam, relanium, diazepam, tramadol, clonazepam, ritalin, adderall, mysimba, saxenda, ozempic, adipex, zolpidem, sanval, testosteron, boldenon, trenbolon, winstrol, clenbuterol, oxandrolon, anavar, deca durabolin, omnadren, metanabol, hgh, somatropina, clomid, cyjanek, strychnina, arsenik, pentobarbital, nembutal'),
('check_ip_user', '1'),
('code_body', ''),
('code_head', ''),
('code_style', ''),
('currency', 'zł'),
('days_before_refresh', '7'),
('days_default', '30'),
('days_refresh', '30'),
('days_to_remove', '14'),
('description', ''),
('dotpay_currency', 'PLN'),
('dotpay_id', ''),
('dotpay_pin', ''),
('dotpay_test_mode', ''),
('email', ''),
('enable_articles', '1'),
('facebook_api', ''),
('facebook_lang', 'pl_PL'),
('facebook_login', '0'),
('facebook_secret', ''),
('facebook_side_panel', '0'),
('favicon', '/upload/images/favicon.png'),
('footer_bottom', '<p class="mb-2">antyki.pl - Portal i Giełda Antyków & Militariów</p>\r\n'),
('footer_text', '<p class="small">antyki.pl &copy; 2026 Wszystkie prawa zastrzeżone</p>'),
('footer_top', '<p><strong>antyki.pl</strong> to ogólnopolski portal ogłoszeniowy stworzony specjalnie dla kolekcjonerów i pasjonatów historii. Znajdziesz tu ogłoszenia z zakresu antyków, militariów, numizmatyki, dawnej broni, mebli, zegarów, dzieł sztuki oraz unikalnych staroci z całej Polski.</p>\r\n\r\n'),
('generate_sitemap', '1'),
('google_login','0'),
('google_id',''),
('google_maps', ''),
('google_maps_api', ''),
('google_maps_lat', '52.072754'),
('google_maps_long', '19.028321'),
('google_maps_zoom_add', '5'),
('google_maps_zoom_offer', '10'),
('google_secret',''),
('hide_data_not_logged', '1'),
('hide_email', '0'),
('hide_phone', '0'),
('hide_views', '0'),
('index_box_subcategories','1'),
('index_page', '<h3 style="text-align: center;">WITAJ W ANTYKI.PL</h3>\r\n\r\n<p style="text-align: center;">Witaj w <strong>antyki.pl</strong> – darmowym portalu ogłoszeniowym przeznaczonym dla miłośników antyków, militariów, numizmatyki, dzieł sztuki oraz unikalnych staroci z minionych epok. Kupuj, sprzedawaj i wymieniaj wyjątkowe przedmioty z historią!&nbsp;</p>\r\n'),
('keywords', 'antyki, militaria, starocie, kolekcjonerstwo, numizmatyka, zabytki, meble antyczne, odznaczenia, broń biała, monety, antyki.pl'),
('lang', 'pl'),
('limit_page', '10'),
('limit_page_index', '12'),
('limit_similar_offer', '3'),
('lk', ''),
('ln', ''),
('login_page', '<h2>antyki.pl - Portal Ogłoszeniowy</h2>\r\n\r\n<h4>&nbsp;</h4>\r\n\r\n<h4>Zakładając konto w naszym serwisie uzyskasz dostęp do:</h4>\r\n\r\n<ul>\r\n	<li>Darmowego dodawania i zarządzania swoimi ogłoszeniami antyków i militariów</li>\r\n	<li>Wszystkich swoich ofert w jednym wygodnym miejscu</li>\r\n	<li>Możliwości dodawania ogłoszeń do ulubionych / schowka</li>\r\n	<li>Wygodnej komunikacji z kupującymi i sprzedającymi</li>\r\n	<li>Personalizacji swojego profilu kolekcjonera</li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n'),
('logo', '/upload/images/logo.png'),
('logo_facebook', '/upload/images/logo_facebook.png'),
('mail_attachment', '1'),
('number_char_title','128'),
('p24_crc', ''),
('p24_pos_id', ''),
('p24_merchant_id', ''),
('p24_sandbox', ''),
('p24_api_key', ''),
('pay_by_dotpay', '0'),
('pay_by_p24',''),
('pay_by_paypal', '0'),
('paypal_currency', 'PLN'),
('paypal_email', ''),
('paypal_lc', 'PL'),
('paypal_test_mode', ''),
('photo_add', '1'),
('photo_max', '10'),
('photo_max_height', '0'),
('photo_max_size', '0'),
('photo_max_width', '0'),
('photo_quality', '75'),
('promote_cost', '2.46'),
('promote_days', '7'),
('promote_only_by_author','0'),
('required_address','0'),
('required_category', '1'),
('required_phone','0'),
('required_state', '0'),
('required_subcategory', '0'),
('required_type', '0'),
('rss', '1'),
('rodo_alert', ''),
('rodo_alert_text', '<p>Szanowny użytkowniku,<br />\r\npragniemy Cię poinformować, że nasz serwis internetowy może personalizować treści marketingowe do Twoich potrzeb. W związku z tym danymi osobowymi, kt&oacute;re przetwarzamy są np. Tw&oacute;j adres IP, dane pozyskiwane na podstawie plik&oacute;w cookies lub podobnych mechanizm&oacute;w na Twoim urządzeniu o ile pozwolą one na zidentyfikowanie Ciebie.&nbsp;<br />\r\nJeżeli klikniesz przycisk &bdquo;Wyrażam zgodę na przetwarzanie moich danych osobowych&rdquo; lub zamkniesz to okno, to wyrazisz zgodę na przetwarzanie Twoich danych przez właściciela witryny i jego zaufanych partner&oacute;w.<br />\r\nWyrażenie zgody jest dobrowolne. Masz prawo do: dostępu do Twoich danych, ich sprostowania oraz usunięcia. Więcej informacji odnośnie przetwarzania danych osobowych znajdziesz w naszej <a href=\"/info/1,polityka-prywatnosci\">Polityce Prywatności.</a></p>\r\n\r\n<p>Lista zaufanych partner&oacute;w:<br />\r\nGoogle - na stronie są zamieszczone kody reklam Adsense oraz Google Analytics, kt&oacute;re mają na celu wyświetlanie spersonalizowanych treści oraz zbieranie informacji o zachowaniu użytkownika w celu poprawy strony.<br />\r\nFacebook - na stronie zamieszczony jest kod Facebook mający na celu wyświetlanie boksu z komentarzami oraz panelu bocznego.</p>\r\n'),
('search_box_address', '1'),
('search_box_category', '1'),
('search_box_distance', '1'),
('search_box_keywords', '1'),
('search_box_options', '1'),
('search_box_price', '1'),
('search_box_state', '1'),
('search_box_type', '1'),
('show_breadcrumbs', '1'),
('show_contact_form_offer', '1'),
('show_contact_form_profile', '1'),
('show_number_offers_in_categories', '1'),
('show_similar_offer', '1'),
('show_modernization_alert', '1'),
('smtp', ''),
('smtp_host', ''),
('smtp_mail', ''),
('smtp_password', ''),
('smtp_port', '587'),
('smtp_secure', 'tls'),
('smtp_user', ''),
('social_facebook', '1'),
('social_google_plus', '1'),
('social_pinterest', '1'),
('social_twitter', '1'),
('social_wykop', '1'),
('template', 'default'),
('title', 'Giełda Budowlana'),
('url_facebook', ''),
('url_privacy_policy', 'polityka-prywatnosci'),
('url_rules', 'regulamin'),
('watermark', '/upload/images/watermark.png'),
('watermark_add', '1');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `slider`
--

CREATE TABLE IF NOT EXISTS `slider` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Zrzut danych tabeli `slider`
--

INSERT INTO `slider` (`id`, `content`) VALUES
(1, '<p><img alt=\"Giełda Budowlana\" class=\"d-block w-100\" src=\"/upload/images/slider1.jpg\" /></p>\r\n\r\n<div class=\"carousel-caption d-none d-md-block\">\r\n<h3>Nowoczesny portal ogłoszeniowy</h3>\r\n\r\n<p>To jest pokazowa wersja skryptu. Prosimy nie dodawać prawdziwych ogłoszeń</p>\r\n</div>\r\n'),
(2, '<p><img alt=\"Giełda Budowlana\" class=\"d-block w-100\" src=\"/upload/images/slider2.jpg\" /></p>\r\n\r\n<div class=\"carousel-caption d-none d-md-block\">\r\n<h3>STW&Oacute;RZ WŁASNY PORTAL OGŁOSZENIOWY</h3>\r\n\r\n<p>I zacznij zarabiać w ciągu kilku minut!</p>\r\n</div>\r\n'),
(3, '<p><img alt=\"Giełda Budowlana\" class=\"d-block w-100\" src=\"/upload/images/slider3.jpg\" /></p>\r\n\r\n<div class=\"carousel-caption d-none d-md-block\">\r\n<h3>Znajdź szybko dokładnie to czego szukasz!</h3>\r\n\r\n<p>Dzięki zaawansowanej wyszukiwarce możesz znaleźć ogłoszenie idealnie spełniające&nbsp;Twoje oczekiwania.</p>\r\n</div>\r\n');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `state`
--

CREATE TABLE IF NOT EXISTS `state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `subcategory`
--

CREATE TABLE IF NOT EXISTS `subcategory` (
  `category_id` int(11) NOT NULL,
  `subcategory_id` int(11) NOT NULL,
  `count` int(11) DEFAULT NULL,
  KEY `category_id` (`category_id`),
  KEY `subcategory_id` (`subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `type`
--

CREATE TABLE IF NOT EXISTS `type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) DEFAULT NULL,
  `email` varchar(64) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `moderator` tinyint(1) DEFAULT NULL,
  `description` text,
  `address` varchar(512) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `state2_id` int(11) DEFAULT NULL,
  `activation_code` varchar(64) NOT NULL,
  `avatar` varchar(256) DEFAULT NULL,
  `register_fb` tinyint(1) DEFAULT NULL,
  `register_google` tinyint(1) DEFAULT NULL,
  `register_ip` varchar(40) NOT NULL,
  `activation_date` datetime DEFAULT NULL,
  `activation_ip` varchar(40) DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `suggestions`
--

CREATE TABLE IF NOT EXISTS `suggestions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('feature','improvement','bug') NOT NULL DEFAULT 'feature',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','approved','rejected','implemented') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `suggestion_votes`
--

CREATE TABLE IF NOT EXISTS `suggestion_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `suggestion_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `vote` tinyint(2) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suggestion_user` (`suggestion_id`,`user_id`),
  KEY `suggestion_ip` (`suggestion_id`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `chat_room`
--

CREATE TABLE IF NOT EXISTS `chat_room` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offer_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `last_notified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_offer_buyer` (`offer_id`, `buyer_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `seller_id` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `chat_message`
--

CREATE TABLE IF NOT EXISTS `chat_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Ograniczenia dla zrzutów tabel
--

--
-- Ograniczenia dla tabeli `clipboard`
--
ALTER TABLE `clipboard`
  ADD CONSTRAINT `clipboard_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `clipboard_ibfk_2` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`id`);

--
-- Ograniczenia dla tabeli `option_category`
--
ALTER TABLE `option_category`
  ADD CONSTRAINT `option_category_ibfk_1` FOREIGN KEY (`option_id`) REFERENCES `option` (`id`),
  ADD CONSTRAINT `option_category_ibfk_2` FOREIGN KEY (`option_category`) REFERENCES `category` (`id`);

--
-- Ograniczenia dla tabeli `option_value`
--
ALTER TABLE `option_value`
  ADD CONSTRAINT `option_value_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`id`),
  ADD CONSTRAINT `option_value_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `option` (`id`);

--
-- Ograniczenia dla tabeli `reset_password`
--
ALTER TABLE `reset_password`
  ADD CONSTRAINT `reset_password_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

SET FOREIGN_KEY_CHECKS = 1;

