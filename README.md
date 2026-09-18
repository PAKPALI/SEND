# SEND

Mini-application Laravel pour créer des campagnes SMS et WhatsApp de groupe avec KprimeSMS/KprimeWhatsApp et l’achat de quotas via KPrimePay.

## Fonctionnalités

- création de compte, connexion et déconnexion ;
- carnet de contacts avec ajout unitaire et import CSV ;
- groupes de contacts avec sélection multiple ;
- campagnes SMS ou WhatsApp mises en file dans la queue Laravel ;
- historique filtrable des livraisons, statut par destinataire et renvoi des échecs ;
- crédits SMS/WhatsApp séparés, achat KPrimePay et webhook V1/V2 idempotent ;
- interface responsive en soft UI / néomorphisme sombre.

## Démarrage local

1. Installer les dépendances :

~~~bash
composer install
npm install
~~~

2. Renseigner le fichier .env. Le .env fourni localement reprend les paramètres Kprime utilisés dans C:/POS, avec une base MySQL dédiée kprime_flow. Ne jamais versionner ce fichier.

3. Créer la base puis migrer :

~~~bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS kprime_flow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
~~~

4. Compiler l’interface :

~~~bash
npm run build
~~~

5. Lancer l’application et le worker dans deux terminaux :

~~~bash
php artisan serve
php artisan queue:work database --queue=campaigns --tries=3 --timeout=120
~~~

Le callback KPrimePay est exposé sur POST /api/kprimepay/webhook et le callback KPrimeSMS sur POST /api/sms/callback.

## Notes d’intégration

Les appels KprimeSMS reprennent les endpoints utilisés par le POS :

- SMS : POST {KPRIME_SMS_BASE_URL}/sms/push
- WhatsApp : POST {KPRIME_SMS_BASE_URL}/whatsapp/template/text-message

Les crédits ne sont décrémentés qu’après une réponse fournisseur acceptée (status=true, 1 ou "1"). Les paiements sont vérifiés via /transactions/debit-status avant d’ajouter les quotas.

## Vérifications effectuées

~~~bash
php artisan test
npm run build
php artisan migrate:status
~~~
