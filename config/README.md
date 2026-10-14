# Configurazione The DALO

Questa cartella contiene la configurazione cifrata generata dall'installer. Non pubblicare mai i suoi file su Git e mantieni attivo il blocco web di `.htaccess`.

Le credenziali PostgreSQL non vengono hashate: l'applicazione deve poterle recuperare per aprire la connessione. Vengono cifrate con `sodium_crypto_secretbox`; la chiave applicativa viene salvata separatamente in `config/.key` e deve avere permessi filesystem restrittivi (`0600`).
