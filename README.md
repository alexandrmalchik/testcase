# testcase
This is test project with requirement
How to run?
1. At first clone project and go to project (cd example-app)
3. Then composer install
4. php artisan migrate
5. php artisan db:seed for create user
6. first enpoint for login user
  METHOD: POST "http://127.0.0.1:8000/api/auth/login?email=test@example.com&password=12345678&password_confirmation=12345678"
  with params: {
  email=test@example.com
  password=12345678
  password_confirmation=12345678
7. endpoint for test (add token from Login endpoint) p.s. Auth type Bearer token
   METHOD POST http://127.0.0.1:8000/api/auth/verify
   with params: file => attach file

   Also I'm attached Postman collection named "test_collection.json
