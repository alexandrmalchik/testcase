# testcase
This is test project with requirements from https://accredify.notion.site/Technical-Assessment-for-Backend-Software-Engineer-2024-bdafdf7d218d4d549c554a03d20b842a#f1f71bde4040481aac8cc2e1d366efeb


1. At first clone project
2. Then composer install
3. php artisan migrate
4. php artisan db:seed for create user
5. first enpoint for login user
  METHOD: POST "http://127.0.0.1:8000/api/auth/login?email=test@example.com&password=12345678&password_confirmation=12345678"
  with params: {
  email=test@example.com
  password=12345678
  password_confirmation=12345678
7. endpoint for test (add token from Login endpoint) p.s. Auth type Bearer token
   METHOD POST http://127.0.0.1:8000/api/auth/verify
   with params: file => attach file

   Also I'm attached Postman collection named "test_collection.json
