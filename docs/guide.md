trees-framework/
│
├── app/
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── HomeController.php
│   │   └── UserController.php
│   │
│   ├── Models/
│   │   ├── Model.php (Base Model)
│   │   └── User.php
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── CsrfMiddleware.php
│   │   └── ValidationMiddleware.php
│   │
│   └── Views/
│       ├── layouts/
│       │   ├── app.php
│       │   └── guest.php
│       ├── components/
│       │   ├── header.php
│       │   └── footer.php
│       ├── home/
│       │   └── index.php
│       └── errors/
│           ├── 404.php
│           └── 500.php
│
├── config/
│   ├── app.php
│   ├── database.php
│   ├── security.php
│   └── routes.php
│
├── public/
│   ├── index.php (Entry point)
│   ├── .htaccess
│   ├── assets/
│   │   ├── css/
│   │   │   ├── app.css
│   │   │   └── tailwind.css
│   │   ├── js/
│   │   │   └── app.js
│   │   └── images/
│   └── uploads/ (writable)
│
├── src/
│   ├── Application.php
│   │
│   ├── Http/
│   │   ├── MiddlewareDispatcher.php
│   │   ├── RequestHandler.php
│   │   ├── ResponseFactory.php
│   │   ├── Router.php (NEW - Advanced routing)
│   │   │
│   │   ├── Message/
│   │   │   ├── Response.php
│   │   │   ├── ServerRequest.php
│   │   │   ├── Stream.php (FIXED)
│   │   │   └── Uri.php
│   │   │
│   │   └── Middleware/
│   │       ├── JsonMiddleware.php
│   │       ├── RoutingMiddleware.php
│   │       ├── SecurityHeadersMiddleware.php (NEW)
│   │       ├── CsrfMiddleware.php (NEW)
│   │       └── RateLimitMiddleware.php (NEW)
│   │
│   ├── Database/
│   │   ├── Connection.php
│   │   ├── QueryBuilder.php
│   │   └── Migration.php
│   │
│   ├── View/
│   │   ├── View.php
│   │   ├── ViewEngine.php
│   │   └── ViewCompiler.php
│   │
│   ├── Security/
│   │   ├── Csrf.php
│   │   ├── Validator.php
│   │   ├── Sanitizer.php
│   │   ├── Encrypter.php
│   │   └── Hash.php
│   │
│   ├── Session/
│   │   ├── Session.php
│   │   └── SessionManager.php
│   │
│   └── Support/
│       ├── Helpers.php
│       ├── Container.php (DI Container)
│       └── Config.php
│
├── storage/
│   ├── cache/
│   ├── logs/
│   ├── sessions/
│   └── views/ (compiled views)
│
├── tests/
│   ├── Unit/
│   └── Integration/
│
├── vendor/ (Composer dependencies)
│
├── .env (Environment variables)
├── .env.example
├── .gitignore
├── composer.json
├── composer.lock
├── tailwind.config.js
└── README.md