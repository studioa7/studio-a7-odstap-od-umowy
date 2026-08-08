=== Studio A7 – Odstąp od umowy ===
Contributors:      studioa7
Tags:              woocommerce, prawo odstąpienia od umowy, consumer rights, eu directive, withdrawal
Requires at least: 6.0
Tested up to:      6.8
Requires PHP:      8.0
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Profesjonalny przycisk „Odstąp od umowy" dla WooCommerce zgodny z Dyrektywą UE 2023/2673.

== Opis ==

Wtyczka **Studio A7 – Odstąp od umowy** umożliwia klientom łatwe złożenie oświadczenia
o odstąpieniu od umowy bezpośrednio w panelu zamówień WooCommerce. Wdrożenie jest w pełni
zgodne z **Dyrektywą UE 2023/2673** (obowiązuje od 19 czerwca 2026 r.).

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

= Wymogi prawne (Dyrektywa UE 2023/2673) =

Od 19 czerwca 2026 r. sklepy internetowe sprzedające konsumentom w UE muszą:
1. Udostępnić wyraźnie oznaczony przycisk inicjujący odstąpienie od umowy
2. Wdrożyć dwuetapowy proces (złożenie oświadczenia + potwierdzenie)
3. Niezwłocznie przesłać potwierdzenie na trwałym nośniku (email) z datą i godziną

Niedostosowanie się grozi karą do **4% rocznego obrotu** i wydłużeniem terminu
na odstąpienie do **12 miesięcy i 14 dni**.

= Autor =

Wtyczka stworzona przez [Studio A7](https://studio-a7.pl) – agencja WordPress & WooCommerce.

== Instalacja ==

1. Wgraj folder `studio-a7-odstap-od-umowy` do `/wp-content/plugins/`
2. Aktywuj wtyczkę w panelu **Wtyczki → Zainstalowane wtyczki**
3. Przejdź do **WooCommerce → Odstąp – Ustawienia** aby skonfigurować
4. Sprawdź emaile w **WooCommerce → Ustawienia → Email**

== Często zadawane pytania ==

= Czy wtyczka jest zgodna z prawem polskim? =

Tak. Wtyczka implementuje wymogi Dyrektywy UE 2023/2673, która transponuje przepisy
do prawa polskiego (Ustawa o prawach konsumenta). Zalecamy konsultację z prawnikiem
w celu dostosowania treści emaili i regulaminu sklepu.

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

= 1.0.0 =
* Pierwsze wydanie
* Dwuetapowy proces odstąpienia (Dyrektywa UE 2023/2673)
* Email potwierdzający z datą i godziną złożenia oświadczenia
* Panel zarządzania wnioskami z eksportem CSV
* Wyjątki: produkty wirtualne, cyfrowe, kategorie na zamówienie
* Metabox i kolumna w panelu zamówień
* Zgodność z HPOS WooCommerce
* Licznik pozostałych dni na odstąpienie
