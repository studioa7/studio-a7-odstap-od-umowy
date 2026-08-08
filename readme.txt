=== Studio A7 – Odstąp od umowy ===
Contributors:      studioa7
Tags:              woocommerce, prawo odstąpienia od umowy, consumer rights, eu directive, withdrawal
Requires at least: 6.0
Tested up to:      6.8
Requires PHP:      8.0
Stable tag:        1.1.1
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Profesjonalny przycisk „Odstąp od umowy" dla WooCommerce, wspierający obsługę oświadczeń klientów.

== Opis ==

Wtyczka **Studio A7 – Odstąp od umowy** umożliwia klientom łatwe złożenie oświadczenia
o odstąpieniu od umowy bezpośrednio w panelu zamówień WooCommerce. Wtyczka wspiera
dwuetapowe złożenie oświadczenia i trwałe potwierdzenie e-mail.

= Kluczowe funkcje =

* **Przycisk w panelu klienta** – widoczny na liście zamówień i na stronie szczegółów
* **Dwuetapowy proces** – formularz + potwierdzenie (wymóg dyrektywy UE)
* **Email potwierdzający** – wysyłany natychmiast z datą i godziną złożenia oświadczenia (trwały nośnik)
* **Powiadomienie admina** – email do obsługi klienta o nowym wniosku
* **Panel zarządzania** – lista wszystkich wniosków z filtrowaniem i eksportem CSV
* **Wyjątki produktów** – automatyczne blokowanie dla produktów wirtualnych, cyfrowych i wybranych kategorii
* **Licznik dni** – informacja o pozostałym czasie na odstąpienie
* **Metabox w zamówieniu** – widoczny status odstąpienia bezpośrednio w panelu zamówień
* **Kolumna w tabeli zamówień** – szybki podgląd statusu dla każdego zamówienia
* **Zgodny z HPOS** – działa z High-Performance Order Storage WooCommerce

= Ważna informacja prawna =

Wtyczka jest narzędziem technicznym i nie stanowi porady prawnej ani gwarancji zgodności z przepisami. Sprzedawca odpowiada za weryfikację podstawy prawnej, wyjątków, terminów, treści komunikacji oraz polityki retencji danych dla swojej działalności i rynków docelowych.

= Autor =

Wtyczka stworzona przez [Studio A7](https://studio-a7.pl) – agencja WordPress & WooCommerce.

== Instalacja ==

1. Wgraj folder `studio-a7-odstap-od-umowy` do `/wp-content/plugins/`
2. Aktywuj wtyczkę w panelu **Wtyczki → Zainstalowane wtyczki**
3. Przejdź do **WooCommerce → Odstąp – Ustawienia** aby skonfigurować
4. Sprawdź emaile w **WooCommerce → Ustawienia → Email**

== Często zadawane pytania ==

= Czy wtyczka jest zgodna z prawem polskim? =

Wtyczka wspiera techniczną obsługę oświadczeń. Nie zastępuje analizy prawnej; skonsultuj konfigurację, treść e-maili i regulamin z prawnikiem.

= Czy mogę wyłączyć prawo odstąpienia dla konkretnych produktów? =

Tak. W ustawieniach możesz wykluczyć: produkty wirtualne, treści cyfrowe (po pobraniu)
oraz całe kategorie produktów (np. „Towary szyte na miarę", „Żywność").

= Czy wtyczka działa z HPOS? =

Tak. Wtyczka jest zgodna z High-Performance Order Storage (Custom Order Tables) WooCommerce.

= Gdzie znajdę listę wszystkich wniosków? =

W panelu WordPress: **WooCommerce → Odstąpienia od umowy**. Możesz też eksportować dane do CSV.

= Czy email do klienta można edytować? =

Tak. Treść i temat emaila edytujesz w **WooCommerce → Ustawienia → Email → Studio A7 – Potwierdzenie odstąpienia (klient)**.

== Zrzuty ekranu ==

1. Przycisk „Odstąp od umowy" w panelu klienta (lista zamówień)
2. Modal – Krok 1: Formularz oświadczenia
3. Modal – Krok 2: Potwierdzenie decyzji
4. Modal – Sukces: Oświadczenie złożone
5. Panel administracyjny – lista zgłoszeń
6. Panel administracyjny – ustawienia
7. Metabox w szczegółach zamówienia (admin)

== Dziennik zmian ==

= 1.1.1 =
* Bezpieczne sprawdzanie zależności WooCommerce podczas aktywacji
* Ochrona przed błędem krytycznym, gdy plik klasy nie został wdrożony

= 1.0.0 =
* Pierwsze wydanie
* Dwuetapowy proces odstąpienia (Dyrektywa UE 2023/2673)
* Email potwierdzający z datą i godziną złożenia oświadczenia
* Panel zarządzania wnioskami z eksportem CSV
* Wyjątki: produkty wirtualne, cyfrowe, kategorie na zamówienie
* Metabox i kolumna w panelu zamówień
* Zgodność z HPOS WooCommerce
* Licznik pozostałych dni na odstąpienie

= 1.1.0 =
* Wzmocniona kontrola właściciela zamówienia i walidacja po stronie serwera
* Atomowe potwierdzanie oświadczeń oraz ograniczenie danych technicznych
* Konfigurowalna retencja danych i opcjonalne usuwanie ich przy odinstalowaniu
* Termin obliczany od daty ukończenia zamówienia z bezpiecznym fallbackiem
