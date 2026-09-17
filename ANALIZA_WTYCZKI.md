# Analiza Wtyczki: Studio A7 – Odstąpienie od umowy for WooCommerce

## 📋 Informacje Podstawowe

**Nazwa:** Studio A7 – Odstąpienie od umowy for WooCommerce  
**Wersja:** 2.0.0  
**Autor:** Studio A7 (https://studio-a7.pl)  
**Licencja:** GPLv2 or later  
**Text Domain:** studio-a7-odstap  

## 🎯 Cel Wtyczki

Wtyczka umożliwia klientom sklepów WooCommerce łatwe złożenie oświadczenia o odstąpieniu od umowy zgodnie z wymogami prawa konsumenckiego UE. Zapewnia pełną obsługę procesu odstąpienia od umowy, od złożenia wniosku przez klienta, przez powiadomienia email, aż po zarządzanie wnioskami w panelu administracyjnym.

## ⚙️ Wymagania Techniczne

### Minimalne Wymagania:
- **WordPress:** 6.0 lub nowszy
- **PHP:** 8.0 lub nowszy
- **WooCommerce:** 7.0 lub nowszy (testowane do 9.x)
- **Zależności:** WooCommerce (wymagane)

### Zgodność:
- ✅ HPOS (High-Performance Order Storage) - Custom Order Tables
- ✅ WordPress Multisite
- ✅ Dyrektywy UE dotyczące praw konsumenta

## 📁 Struktura Projektu

```
studio-a7-odstap-od-umowy/
├── admin/                          # Panel administracyjny
│   ├── css/
│   │   └── admin.css              # Style panelu admin
│   └── views/
│       ├── requests-list.php      # Lista wniosków
│       └── settings-page.php      # Strona ustawień
├── emails/                         # Obsługa emaili
│   ├── class-a7-email-admin-notification.php
│   ├── class-a7-email-customer-withdrawal.php
│   └── views/
│       ├── email-admin-notification.php
│       └── email-customer-withdrawal.php
├── includes/                       # Główna logika wtyczki
│   ├── class-a7-withdrawal-admin.php      # Panel administracyjny
│   ├── class-a7-withdrawal-db.php         # Operacje bazodanowe
│   ├── class-a7-withdrawal-email.php      # Zarządzanie emailami
│   ├── class-a7-withdrawal-form-fields.php # Pola formularza
│   ├── class-a7-withdrawal-handler.php    # Logika biznesowa
│   ├── class-a7-withdrawal-main.php       # Klasa główna
│   └── class-a7-withdrawal-rules.php      # Reguły kwalifikacji
├── public/                         # Frontend
│   ├── css/
│   │   └── public.css             # Style frontendu
│   ├── js/
│   │   └── public.js              # JavaScript frontendu
│   └── views/
│       ├── button.php             # Przycisk odstąpienia
│       └── modal.php              # Modal z formularzem
├── tests/                          # Testy
│   ├── bootstrap.php
│   └── integration/
│       └── test-withdrawal-lifecycle.php
├── .gitignore
├── phpunit.xml.dist               # Konfiguracja PHPUnit
├── readme.txt                     # Dokumentacja WordPress
├── studio-a7-odstap-od-umowy.php  # Główny plik wtyczki
└── uninstall.php                  # Skrypt deinstalacji
```

## 🔑 Kluczowe Funkcje

### 1. **Dwuetapowy Proces Odstąpienia**
- **Krok 1:** Formularz oświadczenia z wyborem produktów i powodem
- **Krok 2:** Potwierdzenie decyzji (wymóg dyrektywy UE)
- **Sukces:** Natychmiastowe potwierdzenie z datą i godziną

### 2. **Dostęp dla Różnych Typów Klientów**
- **Zalogowani klienci:** Przycisk w panelu "Moje konto"
- **Goście:** Shortcode `[a7w_guest_withdrawal]` z weryfikacją:
  - Numer zamówienia
  - Email rozliczeniowy
  - Klucz zamówienia (z potwierdzenia zakupu)
  - Sesja ograniczona do 15 minut

### 3. **System Powiadomień Email**
- **Email do klienta:** Potwierdzenie odstąpienia (trwały nośnik)
- **Email do admina:** Powiadomienie o nowym wniosku
- **Edytowalne szablony:** WooCommerce → Ustawienia → Email

### 4. **Panel Zarządzania Wnioskami**
- Lista wszystkich wniosków z filtrowaniem
- Eksport do CSV
- Metabox w szczegółach zamówienia
- Kolumna statusu w tabeli zamówień
- Historia wniosków klienta
- Dziennik audytu

### 5. **Konfigurowalne Reguły Kwalifikacji**
- **Wyjątki produktów:**
  - Produkty wirtualne
  - Produkty cyfrowe (po pobraniu)
  - Wybrane kategorie produktów
- **Ograniczenia:**
  - Metody płatności
  - Metody dostawy
  - Role klientów
  - Konkretne produkty

### 6. **Konfigurowalne Pola Formularza**
- Dodatkowe pola z walidacją
- Obsługa załączników (PDF/JPG/PNG)
- Bezpieczne przechowywanie plików

### 7. **Pełny Cykl Obsługi**
- Anulowanie oczekującego wniosku
- Aktualizacja danych przesyłki zwrotnej
- Panel decyzji administratora z notatką
- Opcjonalne utworzenie kuponu lub zwrotu
- Powiadomienia o decyzji, anulowaniu i zapisaniu danych

### 8. **Licznik Dni**
- Automatyczne obliczanie pozostałego czasu na odstąpienie
- Domyślnie 14 dni (konfigurowalne)
- Blokada po upływie terminu

## 🏗️ Architektura Kodu

### Główne Klasy:

#### 1. `A7_Withdrawal_Main` ([`includes/class-a7-withdrawal-main.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\includes\class-a7-withdrawal-main.php))
- Singleton - główna klasa wtyczki
- Rejestracja hooków WordPress/WooCommerce
- Obsługa AJAX (krok 1 i krok 2)
- Rejestracja assetów (CSS/JS)
- Wyświetlanie przycisków w panelu klienta

#### 2. `A7_Withdrawal_Handler` ([`includes/class-a7-withdrawal-handler.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\includes\class-a7-withdrawal-handler.php))
- Logika biznesowa
- Sprawdzanie kwalifikowalności zamówienia
- Przetwarzanie formularzy (krok 1 i 2)
- Generowanie tokenów bezpieczeństwa
- Triggering emaili

#### 3. `A7_Withdrawal_DB` ([`includes/class-a7-withdrawal-db.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\includes\class-a7-withdrawal-db.php))
- Singleton - operacje bazodanowe
- CRUD dla wniosków o odstąpienie
- Tworzenie/aktualizacja tabel
- Zapytania i statystyki

#### 4. `A7_Withdrawal_Admin` ([`includes/class-a7-withdrawal-admin.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\includes\class-a7-withdrawal-admin.php))
- Panel administracyjny
- Lista wniosków z filtrowaniem
- Strona ustawień
- Metabox w zamówieniach
- Kolumna w tabeli zamówień
- Eksport CSV

#### 5. `A7_Withdrawal_Email` ([`includes/class-a7-withdrawal-email.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\includes\class-a7-withdrawal-email.php))
- Rejestracja klas emaili WooCommerce
- Zarządzanie szablonami emaili

#### 6. `A7_Withdrawal_Form_Fields` ([`includes/class-a7-withdrawal-form-fields.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\includes\class-a7-withdrawal-form-fields.php))
- Definicja pól formularza
- Walidacja danych wejściowych
- Obsługa załączników

#### 7. `A7_Withdrawal_Rules` ([`includes/class-a7-withdrawal-rules.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\includes\class-a7-withdrawal-rules.php))
- Reguły kwalifikacji zamówień
- Sprawdzanie wyjątków produktowych
- Weryfikacja metod płatności/dostawy

### Klasy Email:

#### 8. `A7_Email_Customer_Withdrawal` ([`emails/class-a7-email-customer-withdrawal.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\emails\class-a7-email-customer-withdrawal.php))
- Email potwierdzający dla klienta
- Trwały nośnik z datą i godziną

#### 9. `A7_Email_Admin_Notification` ([`emails/class-a7-email-admin-notification.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\emails\class-a7-email-admin-notification.php))
- Powiadomienie dla administratora
- Informacja o nowym wniosku

## 🔐 Bezpieczeństwo

### Implementowane Mechanizmy:
1. **Nonce Verification** - Weryfikacja tokenów CSRF
2. **Capability Checks** - Sprawdzanie uprawnień użytkowników
3. **Data Sanitization** - Czyszczenie danych wejściowych
4. **Data Validation** - Walidacja wszystkich danych
5. **Prepared Statements** - Bezpieczne zapytania SQL
6. **HttpOnly Cookies** - Sesje gości z flagą HttpOnly
7. **Time-Limited Sessions** - Sesje gości ważne 15 minut
8. **File Upload Security** - Ograniczenie typów plików (PDF/JPG/PNG)

## 📊 Baza Danych

Wtyczka tworzy dedykowaną tabelę w bazie danych WordPress do przechowywania wniosków o odstąpienie. Struktura jest zarządzana przez klasę `A7_Withdrawal_DB`.

### Główne Pola:
- ID wniosku
- ID zamówienia
- ID klienta
- Status wniosku
- Data złożenia
- Powód odstąpienia
- Wybrane produkty
- Dane przesyłki zwrotnej
- Notatki administratora
- Dziennik audytu

## 🎨 Frontend

### Assety:
- **CSS:** [`public/css/public.css`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\public\css\public.css) - Style modala i przycisków
- **JavaScript:** [`public/js/public.js`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\public\js\public.js) - Obsługa AJAX i modala

### Widoki:
- **Przycisk:** [`public/views/button.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\public\views\button.php)
- **Modal:** [`public/views/modal.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\public\views\modal.php)

### Integracja z WooCommerce:
- Hook: `woocommerce_my_account_my_orders_actions` - Przycisk w liście zamówień
- Hook: `woocommerce_view_order` - Sekcja na stronie szczegółów zamówienia
- Endpoint: `a7w-returns` - Historia zwrotów w "Moje konto"

## 🔧 Instalacja i Konfiguracja

### Kroki Instalacji:

1. **Upload wtyczki:**
   ```
   Wgraj folder do: /wp-content/plugins/studio-a7-odstap-od-umowy/
   ```

2. **Aktywacja:**
   ```
   WordPress Admin → Wtyczki → Zainstalowane wtyczki → Aktywuj
   ```

3. **Konfiguracja podstawowa:**
   ```
   WooCommerce → Odstąp – Ustawienia
   ```
   - Ustaw liczbę dni na odstąpienie (domyślnie 14)
   - Wybierz dozwolone statusy zamówień
   - Skonfiguruj wyjątki produktowe
   - Ustaw reguły kwalifikacji

4. **Konfiguracja emaili:**
   ```
   WooCommerce → Ustawienia → Email
   ```
   - Edytuj szablon "Studio A7 – Potwierdzenie odstąpienia (klient)"
   - Edytuj szablon "Studio A7 – Powiadomienie administratora"

5. **Strona dla gości (opcjonalnie):**
   ```
   Utwórz nową stronę: "Odstąp od umowy"
   Dodaj shortcode: [a7w_guest_withdrawal]
   Opublikuj stronę
   ```

### Konfiguracja Zaawansowana:

#### Wyjątki Produktowe:
- Produkty wirtualne (checkbox)
- Produkty cyfrowe po pobraniu (checkbox)
- Wybrane kategorie produktów (multiselect)

#### Reguły Kwalifikacji:
- Dozwolone metody płatności
- Dozwolone metody dostawy
- Dozwolone role użytkowników
- Wykluczone produkty

#### Pola Formularza:
- Dodatkowe pola tekstowe
- Pola wyboru (checkbox/radio)
- Pola załączników
- Walidacja wymagana/opcjonalna

## 🧪 Testy

Wtyczka zawiera testy integracyjne:
- **Framework:** PHPUnit
- **Konfiguracja:** [`phpunit.xml.dist`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\phpunit.xml.dist)
- **Testy:** [`tests/integration/test-withdrawal-lifecycle.php`](C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy\tests\integration\test-withdrawal-lifecycle.php)

### Uruchomienie Testów:
```bash
cd "C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy"
phpunit
```

## 📝 Changelog

### Wersja 2.0.0 (Aktualna)
- ✨ Konfigurowalne pola formularza z walidacją
- 📎 Bezpieczna obsługa załączników (PDF/JPG/PNG)
- 🎯 Konfigurowalne reguły kwalifikacji
- 📊 Historia wniosków klienta
- ❌ Anulowanie oczekującego wniosku
- 📦 Aktualizacja danych przesyłki zwrotnej
- 👨‍💼 Panel decyzji administratora z notatką
- 📋 Dziennik audytu
- 🎟️ Opcjonalne utworzenie kuponu lub zwrotu
- 📧 Powiadomienia o decyzji, anulowaniu i zapisaniu danych
- 🧪 Testy integracyjne

### Wersja 1.1.4
- Poprawiono układ wyboru pozycji w formularzu
- Dodano jednoznaczne komunikaty walidacyjne

## ⚖️ Aspekty Prawne

### Disclaimer:
Wtyczka jest **wyłącznie narzędziem technicznym** ułatwiającym obsługę oświadczeń o odstąpieniu od umowy. 

**Nie stanowi:**
- Profesjonalnej porady prawnej
- Gwarancji zgodności z przepisami

**Odpowiedzialność sprzedawcy:**
- Weryfikacja podstawy prawnej
- Określenie wyjątków
- Ustalenie terminów
- Treść komunikacji
- Polityka retencji danych
- Zgodność z przepisami dla swojej działalności i rynków docelowych

### Zalecenia:
- Skonsultuj konfigurację z prawnikiem
- Zweryfikuj treść emaili pod kątem zgodności prawnej
- Dostosuj regulamin sklepu
- Sprawdź wymagania dla swojego rynku (Polska, UE, inne)

## 🔄 Integracje

### WooCommerce:
- ✅ Zamówienia (Orders)
- ✅ Produkty (Products)
- ✅ Kategorie produktów
- ✅ Metody płatności
- ✅ Metody dostawy
- ✅ System emaili
- ✅ Panel "Moje konto"
- ✅ HPOS (Custom Order Tables)

### WordPress:
- ✅ Role i uprawnienia użytkowników
- ✅ Cron (czyszczenie starych wniosków)
- ✅ Shortcodes
- ✅ AJAX
- ✅ Multisite

## 🚀 Wydajność

### Optymalizacje:
- Singleton pattern dla głównych klas
- Lazy loading assetów (tylko tam gdzie potrzebne)
- Prepared statements dla zapytań SQL
- Cron do czyszczenia starych danych
- Indeksowanie tabel bazodanowych

### Cron Jobs:
- **a7w_cleanup_pending** - Codzienne czyszczenie starych wniosków oczekujących

## 📞 Wsparcie

**Autor:** Studio A7
**Website:** https://studioa7.pl
**Repozytorium:** https://github.com/studioa7/studio-a7-odstap-od-umowy

## 📄 Licencja

GPLv2 or later  
https://www.gnu.org/licenses/gpl-2.0.html

---

## 🎯 Podsumowanie dla Developera

### Mocne Strony:
✅ Profesjonalna architektura kodu (OOP, Singleton, separacja logiki)  
✅ Pełna zgodność z HPOS WooCommerce  
✅ Kompleksowe bezpieczeństwo (nonce, sanitization, validation)  
✅ Elastyczna konfiguracja (reguły, pola, wyjątki)  
✅ Testy integracyjne  
✅ Dobrze udokumentowany kod  
✅ Zgodność z dyrektywami UE  

### Obszary do Rozważenia:
⚠️ Wymaga PHP 8.0+ (może ograniczyć kompatybilność ze starszymi serwerami)  
⚠️ Zależność od WooCommerce (nie działa bez niego)  
⚠️ Brak tłumaczeń (tylko polski w readme, ale kod przygotowany pod i18n)  

### Rekomendacje Użycia:
👍 Sklepy WooCommerce w Polsce/UE  
👍 Sklepy wymagające zgodności z prawem konsumenckim  
👍 Profesjonalne wdrożenia e-commerce  
👍 Sklepy z dużą liczbą zamówień  

---

**Data analizy:** 2026-08-24  
**Analizowana wersja:** 2.0.0  
**Lokalizacja:** C:\Users\David\Wtyczki Wordpress\studio-a7-odstap-od-umowy
