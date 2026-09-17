# 🚀 Instrukcja Wdrożenia: Studio A7 – Odstąpienie od umowy v2.0.1

## 📋 Spis Treści
1. [Wymagania Wstępne](#wymagania-wstępne)
2. [Instalacja Wtyczki](#instalacja-wtyczki)
3. [Konfiguracja Podstawowa](#konfiguracja-podstawowa)
4. [Konfiguracja Emaili](#konfiguracja-emaili)
5. [Dokumentacja Prawna](#dokumentacja-prawna)
6. [Testy Funkcjonalne](#testy-funkcjonalne)
7. [Wdrożenie Produkcyjne](#wdrożenie-produkcyjne)
8. [Monitoring i Utrzymanie](#monitoring-i-utrzymanie)

---

## Wymagania Wstępne

### Środowisko Techniczne
- ✅ WordPress 6.0 lub nowszy
- ✅ WooCommerce 7.0 lub nowszy
- ✅ PHP 8.0 lub nowszy
- ✅ MySQL 5.7 lub nowszy (dla transakcji z FOR UPDATE)
- ✅ HTTPS (wymagane dla bezpiecznych cookies)

### Środowisko Staging
⚠️ **WAŻNE:** Przed wdrożeniem na produkcji, przetestuj wtyczkę na środowisku staging!

```bash
# Sprawdź wersję PHP
php -v

# Sprawdź czy WooCommerce jest aktywny
wp plugin list --status=active | grep woocommerce
```

---

## Instalacja Wtyczki

### Metoda 1: Przez Panel WordPress (Zalecana)

1. **Pobierz wtyczkę z GitHub:**
   ```bash
   # Sklonuj repozytorium
   git clone https://github.com/studioa7/studio-a7-odstap-od-umowy.git
   
   # Lub pobierz ZIP
   wget https://github.com/studioa7/studio-a7-odstap-od-umowy/archive/refs/heads/main.zip
   ```

2. **Zainstaluj przez panel:**
   - WordPress Admin → Wtyczki → Dodaj nową → Wgraj wtyczkę
   - Wybierz plik ZIP
   - Kliknij "Zainstaluj teraz"
   - Kliknij "Aktywuj wtyczkę"

### Metoda 2: Przez FTP/SSH

```bash
# Skopiuj folder do wp-content/plugins/
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/studioa7/studio-a7-odstap-od-umowy.git

# Ustaw odpowiednie uprawnienia
chown -R www-data:www-data studio-a7-odstap-od-umowy
chmod -R 755 studio-a7-odstap-od-umowy

# Aktywuj przez WP-CLI
wp plugin activate studio-a7-odstap-od-umowy
```

### Weryfikacja Instalacji

Po aktywacji sprawdź:
- ✅ WooCommerce → Odstąpienia (nowa pozycja w menu)
- ✅ WooCommerce → Odstąp – Ustawienia (nowa pozycja w menu)
- ✅ WooCommerce → Ustawienia → Email (2 nowe emaile)
- ✅ Brak błędów w logach PHP

---

## Konfiguracja Podstawowa

### Krok 1: Ustawienia Ogólne

**WooCommerce → Odstąp – Ustawienia → Ogólne**

```
┌─────────────────────────────────────────────────────────┐
│ Termin odstąpienia:           [14] dni                  │
│                                                           │
│ Dozwolone statusy zamówień:                              │
│ ☑ Ukończone (Completed)                                 │
│ ☑ Przetwarzane (Processing)                             │
│ ☐ Oczekuje na płatność                                  │
│                                                           │
│ Etykieta przycisku:                                      │
│ [Odstąp od umowy]                                        │
│                                                           │
│ ☑ Pokaż licznik dni pozostałych                         │
│ ☐ Wymagaj podania powodu                                │
└─────────────────────────────────────────────────────────┘
```

**Zalecenia:**
- **Termin:** 14 dni (wymóg prawny UE)
- **Statusy:** Tylko "Ukończone" i "Przetwarzane"
- **Licznik dni:** Włącz (informuje klienta)
- **Powód:** Opcjonalnie (nie jest wymagany prawnie)

### Krok 2: Wyjątki Produktowe

**WooCommerce → Odstąp – Ustawienia → Wyjątki**

```
┌─────────────────────────────────────────────────────────┐
│ ☑ Wyklucz produkty wirtualne                            │
│   (usługi, bilety elektroniczne, rezerwacje)            │
│                                                           │
│ ☑ Wyklucz produkty cyfrowe po pobraniu                  │
│   (ebooki, kursy online, oprogramowanie)                │
│                                                           │
│ Wykluczone kategorie produktów:                         │
│ [Wybierz kategorie...]                                   │
│ • Towary na zamówienie                                   │
│ • Żywność                                                │
│ • Kosmetyki (po otwarciu)                               │
│ • Prasa i czasopisma                                     │
└─────────────────────────────────────────────────────────┘
```

**Podstawa prawna:** Art. 16 Dyrektywy 2011/83/UE

### Krok 3: Reguły Kwalifikacji (Opcjonalne)

**WooCommerce → Odstąp – Ustawienia → Reguły**

```
┌─────────────────────────────────────────────────────────┐
│ Dozwolone metody płatności:                              │
│ ☑ Przelew bankowy                                        │
│ ☑ Karta kredytowa                                        │
│ ☐ Pobranie (zalecane wyłączenie)                        │
│                                                           │
│ Dozwolone metody dostawy:                                │
│ ☑ Kurier                                                 │
│ ☑ Paczkomat                                              │
│ ☐ Odbiór osobisty (zalecane wyłączenie)                 │
│                                                           │
│ Wykluczone produkty (ID):                                │
│ [123, 456, 789]                                          │
└─────────────────────────────────────────────────────────┘
```

### Krok 4: Powiadomienia

**WooCommerce → Odstąp – Ustawienia → Powiadomienia**

```
┌─────────────────────────────────────────────────────────┐
│ ☑ Powiadamiaj administratora o nowych wnioskach         │
│                                                           │
│ Adresy email administratorów:                            │
│ [sklep@example.com, obsługa@example.com]                │
│                                                           │
│ Okres retencji danych:                                   │
│ [24] miesięcy                                            │
│                                                           │
│ ☐ Usuń dane przy deinstalacji wtyczki                   │
│   (NIE zalecane - zachowaj dla audytu)                  │
└─────────────────────────────────────────────────────────┘
```

---

## Konfiguracja Emaili

### Email do Klienta (KRYTYCZNE)

**WooCommerce → Ustawienia → Email → Studio A7 – Potwierdzenie odstąpienia (klient)**

#### 1. Podstawowe Ustawienia

```
┌─────────────────────────────────────────────────────────┐
│ ☑ Włącz ten email                                        │
│                                                           │
│ Temat:                                                    │
│ [Potwierdzenie odstąpienia od umowy – zamówienie        │
│  nr {order_number}]                                      │
│                                                           │
│ Nagłówek emaila:                                         │
│ [Odstąpienie od umowy zostało przyjęte]                 │
└─────────────────────────────────────────────────────────┘
```

#### 2. Dodatkowa Treść (WYMAGANE)

⚠️ **WAŻNE:** To pole MUSI być uzupełnione przed wdrożeniem!

```html
<h3>Instrukcje zwrotu towaru</h3>

<p><strong>Adres zwrotu:</strong><br>
[NAZWA FIRMY]<br>
[ULICA I NUMER]<br>
[KOD POCZTOWY] [MIASTO]</p>

<p><strong>Ważne informacje:</strong></p>
<ul>
  <li>Towar należy odesłać w terminie 14 dni od złożenia oświadczenia</li>
  <li>Towar powinien być w stanie nienaruszonym, z oryginalnymi metkami</li>
  <li>Koszty odesłania ponosi klient (chyba że towar jest wadliwy)</li>
  <li>Zwrot środków nastąpi w ciągu 14 dni od otrzymania przesyłki zwrotnej</li>
</ul>

<p><strong>Pytania?</strong><br>
Skontaktuj się z nami: <a href="mailto:sklep@example.com">sklep@example.com</a><br>
Tel: +48 123 456 789</p>
```

**Zastąp:**
- `[NAZWA FIRMY]` → Twoja nazwa firmy
- `[ULICA I NUMER]` → Adres zwrotu
- `[KOD POCZTOWY] [MIASTO]` → Kod i miasto
- `sklep@example.com` → Twój email
- `+48 123 456 789` → Twój telefon

### Email do Administratora

**WooCommerce → Ustawienia → Email → Studio A7 – Powiadomienie administratora**

```
┌─────────────────────────────────────────────────────────┐
│ ☑ Włącz ten email                                        │
│                                                           │
│ Odbiorcy:                                                │
│ [sklep@example.com, obsługa@example.com]                │
│                                                           │
│ Temat:                                                    │
│ [Nowe odstąpienie od umowy – zamówienie {order_number}] │
└─────────────────────────────────────────────────────────┘
```

---

## Dokumentacja Prawna

### 1. Aktualizacja Regulaminu Sklepu

Dodaj sekcję "Odstąpienie od umowy" do regulaminu:

```markdown
## § X. ODSTĄPIENIE OD UMOWY

1. Konsument ma prawo odstąpić od umowy w terminie 14 dni bez podania 
   przyczyny, zgodnie z art. 27 ustawy o prawach konsumenta.

2. Bieg terminu do odstąpienia od umowy rozpoczyna się od dnia, w którym 
   Konsument wszedł w posiadanie rzeczy.

3. Oświadczenie o odstąpieniu od umowy można złożyć:
   a) Elektronicznie poprzez formularz dostępny w panelu "Moje konto" 
      → "Zamówienia" → przycisk "Odstąp od umowy"
   b) Pisemnie na adres: [ADRES FIRMY]
   c) Emailem na adres: [EMAIL]

4. Po złożeniu oświadczenia Konsument otrzyma potwierdzenie na trwałym 
   nośniku (email) z datą i godziną złożenia oświadczenia.

5. Konsument ma obowiązek zwrócić towar niezwłocznie, nie później niż 
   14 dni od dnia złożenia oświadczenia, na adres: [ADRES ZWROTU]

6. Koszty bezpośrednie zwrotu towaru ponosi Konsument.

7. Zwrot środków nastąpi w ciągu 14 dni od dnia otrzymania zwróconego 
   towaru, na rachunek bankowy z którego dokonano płatności.

8. Prawo odstąpienia od umowy nie przysługuje w przypadkach określonych 
   w art. 38 ustawy o prawach konsumenta, w szczególności:
   a) Świadczenia usług, jeżeli przedsiębiorca wykonał w pełni usługę 
      za wyraźną zgodą konsumenta
   b) Nagrań audialnych lub wizualnych albo programów komputerowych 
      dostarczonych w zapieczętowanym opakowaniu, jeżeli opakowanie 
      zostało otwarte po dostarczeniu
   c) Towary ulegające szybkiemu zepsuciu lub mające krótki termin 
      przydatności do użycia
   d) Towary, które po dostarczeniu, ze względu na swój charakter, 
      zostają nierozłącznie połączone z innymi rzeczami
```

### 2. Aktualizacja Polityki Prywatności

Dodaj sekcję o przetwarzaniu danych w kontekście odstąpień:

```markdown
## Przetwarzanie danych w związku z odstąpieniem od umowy

1. **Administrator danych:** [NAZWA FIRMY], [ADRES], [NIP]

2. **Cel przetwarzania:** Obsługa oświadczeń o odstąpieniu od umowy 
   i realizacja zwrotów towarów

3. **Podstawa prawna:** Art. 6 ust. 1 lit. b RODO (wykonanie umowy)

4. **Zakres danych:**
   - Imię i nazwisko
   - Adres email
   - Numer zamówienia
   - Adres IP (dla celów bezpieczeństwa)
   - Powód odstąpienia (opcjonalnie)
   - Data i godzina złożenia oświadczenia

5. **Okres przechowywania:** 24 miesiące od daty złożenia oświadczenia 
   (wymóg księgowy)

6. **Odbiorcy danych:**
   - Obsługa sklepu
   - Firmy kurierskie (w zakresie niezbędnym do odbioru przesyłki)

7. **Prawa osoby, której dane dotyczą:**
   - Prawo dostępu do danych
   - Prawo do sprostowania danych
   - Prawo do usunięcia danych (po upływie okresu retencji)
   - Prawo do ograniczenia przetwarzania
   - Prawo do przenoszenia danych
   - Prawo do wniesienia skargi do UODO
```

### 3. Strona dla Gości (Opcjonalnie)

Utwórz nową stronę: **Odstąp od umowy**

**Treść strony:**

```markdown
# Odstąpienie od umowy dla zamówień gości

Jeśli składałeś zamówienie bez rejestracji konta, możesz złożyć 
oświadczenie o odstąpieniu od umowy poniżej.

**Będziesz potrzebować:**
- Numeru zamówienia (z potwierdzenia zakupu)
- Adresu email użytego przy zamówieniu
- Klucza zamówienia (z potwierdzenia zakupu)

[a7w_guest_withdrawal]

---

**Masz konto?** [Zaloguj się](https://example.com/moje-konto/) 
i złóż oświadczenie w panelu zamówień.
```

**Shortcode:** `[a7w_guest_withdrawal]`

---

## Testy Funkcjonalne

### Przygotowanie Środowiska Testowego

```bash
# 1. Utwórz testowe zamówienie
# 2. Zmień status na "Ukończone"
# 3. Ustaw datę ukończenia na dzisiaj (aby był w terminie)
```

### Test 1: Zalogowany Klient - Pełne Odstąpienie

**Kroki:**
1. Zaloguj się jako klient testowy
2. Przejdź do: Moje konto → Zamówienia
3. Kliknij "Odstąp od umowy" przy testowym zamówieniu
4. **Krok 1:** Wypełnij formularz
   - Wybierz wszystkie produkty w pełnej ilości
   - Podaj powód (opcjonalnie)
   - Zaznacz checkbox zgody
   - Kliknij "Dalej"
5. **Krok 2:** Potwierdź odstąpienie
   - Przeczytaj ostrzeżenie
   - Kliknij "Potwierdzam odstąpienie"
6. **Sukces:** Sprawdź komunikat sukcesu

**Weryfikacja:**
- ✅ Email do klienta otrzymany (sprawdź skrzynkę)
- ✅ Email do admina otrzymany
- ✅ Wniosek widoczny w: WooCommerce → Odstąpienia
- ✅ Notatka dodana do zamówienia
- ✅ Status wniosku: "Potwierdzone"

### Test 2: Gość - Weryfikacja i Odstąpienie

**Kroki:**
1. Wyloguj się
2. Przejdź do strony: /odstap-od-umowy/
3. Wypełnij formularz weryfikacji:
   - Numer zamówienia: `12345`
   - Email: `test@example.com`
   - Klucz zamówienia: `wc_order_xxx` (z emaila potwierdzenia)
4. Kliknij "Zweryfikuj i przejdź dalej"
5. Wypełnij formularz odstąpienia (jak w Test 1)

**Weryfikacja:**
- ✅ Weryfikacja przeszła pomyślnie
- ✅ Formularz odstąpienia wyświetlony
- ✅ Sesja wygasa po 15 minutach
- ✅ Nie można użyć tego samego klucza ponownie

### Test 3: Wyjątki Produktowe

**Test 3a: Produkt Wirtualny**
1. Utwórz zamówienie z produktem wirtualnym (usługa, bilet)
2. Spróbuj odstąpić
3. **Oczekiwany rezultat:** Komunikat "Zamówienie zawiera produkty wirtualne..."

**Test 3b: Produkt Cyfrowy Po Pobraniu**
1. Utwórz zamówienie z produktem cyfrowym (ebook)
2. Pobierz plik (symuluj pobranie)
3. Spróbuj odstąpić
4. **Oczekiwany rezultat:** Komunikat "Treści cyfrowe zostały już pobrane..."

**Test 3c: Wykluczona Kategoria**
1. Dodaj kategorię "Towary na zamówienie" do wykluczonych
2. Utwórz zamówienie z produktem z tej kategorii
3. Spróbuj odstąpić
4. **Oczekiwany rezultat:** Komunikat "Zamówienie zawiera produkty z kategorii wyłączonych..."

### Test 4: Częściowe Odstąpienie

**Kroki:**
1. Utwórz zamówienie z 3 produktami (ilość: 2, 3, 1)
2. Odstąp od części:
   - Produkt 1: ilość 1 (z 2)
   - Produkt 2: ilość 2 (z 3)
   - Produkt 3: ilość 0 (nie odstępujesz)
3. Potwierdź odstąpienie

**Weryfikacja:**
- ✅ Wniosek zawiera poprawne ilości
- ✅ Można złożyć kolejny wniosek o pozostałe ilości
- ✅ Nie można odstąpić więcej niż zamówiono

### Test 5: Anulowanie Wniosku

**Kroki:**
1. Złóż wniosek o odstąpienie (status: Potwierdzone)
2. Przejdź do: Moje konto → Zwroty i odstąpienia
3. Kliknij "Anuluj wniosek"
4. Potwierdź anulowanie

**Weryfikacja:**
- ✅ Status zmieniony na "Anulowane"
- ✅ Email powiadomienia wysłany
- ✅ Można złożyć nowy wniosek

### Test 6: Panel Administratora

**Kroki:**
1. Zaloguj się jako administrator
2. Przejdź do: WooCommerce → Odstąpienia
3. Sprawdź listę wniosków
4. Kliknij "Zatwierdź" lub "Odrzuć" przy wniosku
5. Dodaj notatkę
6. Zapisz decyzję

**Weryfikacja:**
- ✅ Status zmieniony na "Zaakceptowane" lub "Odrzucone"
- ✅ Email decyzji wysłany do klienta
- ✅ Notatka widoczna w szczegółach wniosku
- ✅ Dziennik audytu zaktualizowany

### Test 7: Eksport CSV

**Kroki:**
1. WooCommerce → Odstąpienia
2. Kliknij "Eksportuj CSV"
3. Pobierz plik

**Weryfikacja:**
- ✅ Plik CSV pobrany
- ✅ Zawiera wszystkie wnioski
- ✅ Kodowanie UTF-8 (polskie znaki poprawne)
- ✅ Separator średnik (dla Excela)

---

## Wdrożenie Produkcyjne

### Checklist Przed Wdrożeniem

```
┌─────────────────────────────────────────────────────────┐
│ KONFIGURACJA TECHNICZNA                                 │
├─────────────────────────────────────────────────────────┤
│ ☐ Wtyczka zainstalowana (v2.0.1)                        │
│ ☐ Termin odstąpienia ustawiony (14 dni)                 │
│ ☐ Statusy zamówień wybrane                              │
│ ☐ Wyjątki produktowe skonfigurowane                     │
│ ☐ Email do klienta skonfigurowany + adres zwrotu        │
│ ☐ Email do admina skonfigurowany                        │
│ ☐ Okres retencji ustawiony (24 miesiące)                │
├─────────────────────────────────────────────────────────┤
│ DOKUMENTACJA PRAWNA                                      │
├─────────────────────────────────────────────────────────┤
│ ☐ Regulamin zaktualizowany                              │
│ ☐ Polityka prywatności zaktualizowana                   │
│ ☐ Konsultacja z prawnikiem przeprowadzona               │
│ ☐ Adres zwrotu określony                                │
│ ☐ Procedura zwrotu pieniędzy określona                  │
├─────────────────────────────────────────────────────────┤
│ TESTY                                                    │
├─────────────────────────────────────────────────────────┤
│ ☐ Test 1: Zalogowany klient - PASSED                    │
│ ☐ Test 2: Gość - PASSED                                 │
│ ☐ Test 3: Wyjątki - PASSED                              │
│ ☐ Test 4: Częściowe odstąpienie - PASSED                │
│ ☐ Test 5: Anulowanie - PASSED                           │
│ ☐ Test 6: Panel admina - PASSED                         │
│ ☐ Test 7: Eksport CSV - PASSED                          │
├─────────────────────────────────────────────────────────┤
│ BACKUP I BEZPIECZEŃSTWO                                  │
├─────────────────────────────────────────────────────────┤
│ ☐ Backup bazy danych utworzony                          │
│ ☐ Backup plików utworzony                               │
│ ☐ SSL certyfikat aktywny                                │
│ ☐ PHP 8.0+ zainstalowane                                │
└─────────────────────────────────────────────────────────┘
```

### Procedura Wdrożenia

#### 1. Backup (KRYTYCZNE)

```bash
# Backup bazy danych
wp db export backup-before-a7w-$(date +%Y%m%d).sql

# Backup plików
tar -czf backup-wp-content-$(date +%Y%m%d).tar.gz wp-content/

# Przenieś backupy w bezpieczne miejsce
mv backup-* /path/to/backups/
```

#### 2. Instalacja na Produkcji

```bash
# Metoda 1: Przez Git (zalecana)
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/studioa7/studio-a7-odstap-od-umowy.git
wp plugin activate studio-a7-odstap-od-umowy

# Metoda 2: Przez panel WordPress
# (upload ZIP przez admin panel)
```

#### 3. Konfiguracja

1. **Skopiuj ustawienia ze staging:**
   - Zrób zrzuty ekranu ustawień ze staging
   - Przepisz identyczne wartości na produkcji

2. **Lub użyj WP-CLI:**
   ```bash
   # Eksport ustawień ze staging
   wp option get a7w_withdrawal_days > a7w-settings.txt
   wp option get a7w_allowed_statuses >> a7w-settings.txt
   # ... etc
   
   # Import na produkcji
   wp option update a7w_withdrawal_days 14
   wp option update a7w_allowed_statuses '["wc-completed","wc-processing"]'
   ```

#### 4. Weryfikacja Po Wdrożeniu

```bash
# Sprawdź logi błędów
tail -f /var/log/php-errors.log

# Sprawdź czy wtyczka jest aktywna
wp plugin list | grep studio-a7-odstap-od-umowy

# Sprawdź czy tabela została utworzona
wp db query "SHOW TABLES LIKE 'wp_a7_withdrawals'"
```

#### 5. Test Smoke (Szybki Test)

1. Zaloguj się jako admin
2. Przejdź do: WooCommerce → Odstąpienia
3. Sprawdź czy strona się ładuje
4. Utwórz testowe zamówienie
5. Spróbuj odstąpić (jako klient)
6. Sprawdź czy email przyszedł

---

## Monitoring i Utrzymanie

### Monitoring Dzienny

**Co sprawdzać codziennie:**

```bash
# 1. Nowe wnioski
wp db query "SELECT COUNT(*) FROM wp_a7_withdrawals WHERE status='confirmed' AND DATE(confirmed_at) = CURDATE()"

# 2. Błędy w logach
grep "A7W" /var/log/php-errors.log | tail -20

# 3. Nieprzetworzone wnioski (>24h)
wp db query "SELECT id, order_id, created_at FROM wp_a7_withdrawals WHERE status='confirmed' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
```

### Monitoring Tygodniowy

**Statystyki:**
- Liczba wniosków w tygodniu
- Średni czas obsługi
- Najczęstsze powody odstąpień
- Produkty z największą liczbą odstąpień

**Raport:**
```bash
# Eksportuj dane z ostatniego tygodnia
wp db query "SELECT * FROM wp_a7_withdrawals WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" --format=csv > weekly-report.csv
```

### Czyszczenie Danych (Automatyczne)

Wtyczka automatycznie czyści:
- **Wnioski pending >24h:** Usuwane codziennie (cron)
- **Wnioski confirmed >24 miesiące:** Usuwane zgodnie z ustawieniem retencji

**Sprawdź cron:**
```bash
wp cron event list | grep a7w_cleanup_pending
```

### Aktualizacje

**Sprawdzanie aktualizacji:**
```bash
# Sprawdź czy jest nowa wersja na GitHub
git fetch origin
git log HEAD..origin/main --oneline

# Aktualizuj (po testach na staging!)
git pull origin main
wp plugin deactivate studio-a7-odstap-od-umowy
wp plugin activate studio-a7-odstap-od-umowy
```

### Troubleshooting

#### Problem: Email nie dochodzi

**Diagnoza:**
```bash
# Sprawdź logi WooCommerce
wp wc tool run regenerate_thumbnails

# Sprawdź konfigurację SMTP
wp plugin list | grep smtp

# Test wysyłki
wp eval "wc_mail('test@example.com', 'Test', 'Test message');"
```

**Rozwiązanie:**
- Zainstaluj plugin SMTP (np. WP Mail SMTP)
- Skonfiguruj zewnętrzny serwer SMTP

#### Problem: Formularz nie działa

**Diagnoza:**
```bash
# Sprawdź JavaScript errors w konsoli przeglądarki
# F12 → Console

# Sprawdź czy assets są załadowane
curl -I https://example.com/wp-content/plugins/studio-a7-odstap-od-umowy/public/js/public.js
```

**Rozwiązanie:**
- Wyczyść cache przeglądarki (Ctrl+Shift+Del)
- Sprawdź czy jQuery jest załadowane
- Dezaktywuj inne wtyczki konfliktujące

#### Problem: Sesja gościa wygasa za szybko

**Diagnoza:**
```bash
# Sprawdź TTL sesji
wp option get a7w_guest_session_ttl
```

**Rozwiązanie:**
```php
// Dodaj do functions.php motywu
add_filter('a7w_guest_session_ttl', function($ttl) {
    return 1800; // 30 minut zamiast 15
});
```

#### Problem: Race condition przy częściowych odstąpieniach

**Diagnoza:**
- Sprawdź logi: `grep "ROLLBACK" /var/log/php-errors.log`
- Sprawdź czy MySQL wspiera transakcje (InnoDB)

**Rozwiązanie:**
```bash
# Sprawdź engine tabel
wp db query "SHOW TABLE STATUS WHERE Name='wp_a7_withdrawals'"

# Jeśli MyISAM, zmień na InnoDB
wp db query "ALTER TABLE wp_a7_withdrawals ENGINE=InnoDB"
```

---

## 📞 Wsparcie

### Dokumentacja
- **README:** [readme.txt](readme.txt)
- **Analiza:** [ANALIZA_WTYCZKI.md](ANALIZA_WTYCZKI.md)
- **GitHub:** https://github.com/studioa7/studio-a7-odstap-od-umowy

### Kontakt
- **Email:** kontakt@studio-a7.pl
- **Website:** https://studio-a7.pl

### Zgłaszanie Błędów
- **GitHub Issues:** https://github.com/studioa7/studio-a7-odstap-od-umowy/issues

---

## 📝 Changelog Wdrożenia

### v2.0.1 (2026-09-17) - Wersja Produkcyjna
- ✅ Naprawiono krytyczne problemy bezpieczeństwa
- ✅ Dodano transakcje bazodanowe (race condition)
- ✅ Zabezpieczono sesje gości (replay attacks)
- ✅ Zoptymalizowano eksport CSV
- ✅ Dodano walidację typów
- ✅ Poprawiono obsługę błędów JavaScript
- ✅ Gotowe do wdrożenia produkcyjnego

---

## ✅ Podsumowanie

Wtyczka **Studio A7 – Odstąpienie od umowy v2.0.1** jest gotowa do wdrożenia produkcyjnego po:

1. ✅ Uzupełnieniu adresu zwrotu w emailu do klienta
2. ✅ Aktualizacji regulaminu i polityki prywatności
3. ✅ Przeprowadzeniu testów na środowisku staging
4. ✅ Utworzeniu backupu przed wdrożeniem

**Szacowany czas wdrożenia:** 2-3 dni robocze

**Powodzenia! 🚀**