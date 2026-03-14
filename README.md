# Josix Framework

A lightweight PHP framework with attribute-based routing, autowiring dependency injection, Twig + Tailwind templating, and a database layer supporting **MySQL** and **SQLite**.

---

## Features

| Feature | Description |
|---|---|
| **Attribute Routing** | Define routes with `#[Route]` attributes on controller methods — no config files needed. |
| **Auto-Discovery** | `RouteLocator` scans `app/Controller/` and registers every `#[Route]` automatically. |
| **Dependency Injection** | `Container` with full autowiring. Type-hint your constructor, and dependencies resolve themselves. |
| **Twig + Tailwind** | Twig templates with Tailwind CSS via the Play CDN. A `base.html.twig` layout is included. |
| **Database (MySQL & SQLite)** | `Connection` supports both drivers, switchable via a single `DB_DRIVER` env variable. |
| **ORM Basics** | Base `Model`, `AbstractRepository`, `QueryBuilder`, and relation primitives (`HasMany`, `HasOne`). |
| **Env Loader** | Simple `.env` file loading with `EnvLoader`. |
| **Docker CLI** | `composer josix:live` starts the stack; the CLI health-checks the app before confirming. |

---

## Quick Start

### 1. Clone & install

```bash
git clone https://github.com/joslast/josix-framework.git
cd josix-framework
composer install
```

### 2. Configure environment

Create a `.env` file in the project root:

```dotenv
# SQLite (zero-config, default)
DB_DRIVER=sqlite
DB_PATH=database/josix.sqlite

# — or MySQL —
# DB_DRIVER=mysql
# DBHOST=127.0.0.1
# DBNAME=josix
# DBUSER=root
# DBPASS=secret
```

Or let Josix create a local SQLite-ready setup for you:

```bash
composer josix:db:init
```

This command:
- creates `.env` from `.env.dist` when missing,
- ensures `DB_DRIVER` and `DB_PATH` defaults exist,
- creates the SQLite file (default: `database/josix.sqlite`).

### 3. Start the app

**With Docker (recommended):**

```bash
composer josix:live      # Start the Docker stack
composer josix:restart   # Rebuild & restart
composer josix:stop      # Stop the stack
composer josix:db:init   # Create SQLite file from .env/.env.dist
composer josix:doctor    # Check bind-mount health and file drift
composer josix:sync      # Repair container files from host copy
```

The CLI will wait for the app to respond and print a ✔ or ✘.

**Without Docker:**

```bash
php -S localhost:8000 -t public public/index.php
```

### 4. Open in browser

```
http://localhost/          → Landing page
http://localhost/notes     → SQLite Notes demo (CRUD)
```

---

## Project Structure

```
josix-framework/
├── app/
│   ├── Controller/
│   │   ├── HomeController.php        # Landing page
│   │   └── NoteController.php        # SQLite CRUD demo
│   ├── Core/
│   │   ├── Database/
│   │   │   ├── Connection.php        # PDO wrapper (MySQL + SQLite)
│   │   │   ├── Query.php             # Query representation
│   │   │   ├── QueryBuilder.php      # Builds SELECT queries with relations
│   │   │   └── ValueBag.php          # Parameter bag
│   │   ├── Env/
│   │   │   ├── Env.php               # Static env accessor
│   │   │   └── EnvLoader.php         # .env file parser
│   │   ├── Exception/
│   │   │   └── ServiceNotFoundException.php
│   │   ├── Injection/
│   │   │   └── Container.php         # DI container with autowiring
│   │   └── Routing/
│   │       ├── Route.php             # #[Route] attribute
│   │       ├── RouteCollection.php   # Stores & matches routes
│   │       ├── RouteLocator.php      # Auto-discovers controllers
│   │       └── Router.php            # Dispatches requests
│   ├── Model/
│   │   ├── Model.php                 # Base model with property access
│   │   ├── ModelInterface.php
│   │   ├── Hydrator/
│   │   │   └── HydratorInterface.php
│   │   └── Relation/
│   │       ├── Join.php
│   │       ├── Relation.php          # Relation builder (HasMany/HasOne)
│   │       ├── RelationCollection.php
│   │       └── RelationTypeEnum.php
│   └── Repository/
│       └── AbstractRepository.php    # Base repository (findAll, findBy, …)
├── bin/
│   └── josix                         # CLI entry point
├── database/                         # SQLite database (auto-created, git-ignored)
├── public/
│   └── index.php                     # Front controller
├── templates/
│   ├── base.html.twig                # Base layout (Tailwind CDN)
│   ├── home.html.twig                # Landing page template
│   └── notes.html.twig               # Notes CRUD template
├── .env                              # Environment config (git-ignored)
├── .gitignore
├── composer.json
├── docker-compose.yml
└── README.md
```

---

## Routing

Routes are defined as PHP attributes on controller methods:

```php
use Josix\Core\Routing\Route;

class HomeController
{
    #[Route(path: '/', method: 'GET', name: 'home')]
    public function index(): void { /* … */ }
}
```

The `RouteLocator` auto-discovers every controller in `app/Controller/`. Dynamic segments are supported:

```php
#[Route(path: '/notes/{id}/delete', method: 'POST')]
public function delete(int $id): void { /* … */ }
```

---

## Dependency Injection

The `Container` autowires constructor dependencies. Just type-hint what you need:

```php
class NoteController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Connection $db,
    ) {}
}
```

For interfaces or custom configuration, register factories in `public/index.php`:

```php
$container = new Container([
    Environment::class => function () {
        $loader = new FilesystemLoader(BASE_PATH . '/templates');
        return new Environment($loader, ['cache' => false]);
    },
]);
```

---

## Database

### Configuration

Set the driver in your `.env`:

```dotenv
# SQLite (file-based, zero setup)
DB_DRIVER=sqlite
DB_PATH=database/josix.sqlite

# MySQL
DB_DRIVER=mysql
DBHOST=127.0.0.1
DBNAME=josix
DBUSER=root
DBPASS=secret
```

### Initialize local SQLite database

For local development, run:

```bash
composer josix:db:init
```

Then start the app with Docker:

```bash
composer josix:live
```

### Usage

Inject `Connection` into your controller and use the PDO instance:

```php
// Direct queries
$stmt = $this->db->getPdo()->query('SELECT * FROM notes');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepared statements
$stmt = $this->db->getPdo()->prepare('INSERT INTO notes (title) VALUES (:title)');
$stmt->execute([':title' => 'Hello']);
```

### ORM (advanced)

For MySQL projects with relations, extend `AbstractRepository` and define models with `tableProperties()` and `relations()`:

```php
class NoteRepository extends AbstractRepository
{
    public function __construct(QueryBuilder $qb, Connection $db)
    {
        parent::__construct($qb, $db);
        $this->setTable('notes');
        $this->setModelClass(Note::class);
    }
}
```

---

## Templates

Templates use **Twig** with **Tailwind CSS** (Play CDN). Extend the base layout:

```twig
{% extends 'base.html.twig' %}

{% block title %}My Page{% endblock %}

{% block body_class %}min-h-screen bg-slate-900 text-white{% endblock %}

{% block body %}
    <h1 class="text-3xl font-bold">Hello Josix!</h1>
{% endblock %}
```

---

## Docker

The included `docker-compose.yml` runs a PHP 8.3 built-in server:

```yaml
services:
  josix_example:
    image: php:8.3-cli
    ports:
      - "80:8000"
    command: php -S 0.0.0.0:8000 -t /var/www/public /var/www/public/index.php
    healthcheck:
      test: ["CMD", "php", "-r", "echo @file_get_contents('http://127.0.0.1:8000/') !== false ? 'ok' : exit(1);"]
```

CLI commands:

| Command | Description |
|---|---|
| `composer josix:doctor` | Check bind-mount health and detect host/container drift |
| `composer josix:live` | Start the Docker stack |
| `composer josix:restart` | Stop + start with a health-check |
| `composer josix:sync` | Copy `app/`, `public/`, `templates/`, and `bin/` into the running container |
| `composer josix:stop` | Stop the Docker stack |

### Docker Desktop recovery

If Docker Desktop serves stale or truncated files from the bind mount, run:

```bash
composer josix:doctor
composer josix:sync
```

Use `josix:doctor` to detect bind-mount drift and `josix:sync` to repair the running container without a full rebuild.

### Native Docker Engine on Linux

If you want the most reliable bind-mount setup on Linux, use native Docker Engine instead of Docker Desktop.

Typical migration flow:

```bash
sudo systemctl stop docker-desktop
sudo snap remove docker
sudo apt-get update
sudo apt-get install -y docker.io docker-compose-v2
sudo systemctl enable --now docker
sudo usermod -aG docker $USER
newgrp docker
docker context use default
docker version
docker compose version
```

Then restart your project normally:

```bash
docker compose down --remove-orphans
composer josix:live
```

If your distro does not provide `docker-compose-v2`, install Docker Engine from Docker's official apt repository instead.

---

## SQLite Demo

The framework ships with a **Notes** CRUD demo at `/notes`:

- **List** all notes (`GET /notes`)
- **Create** a note (`POST /notes`)
- **Delete** a note (`POST /notes/{id}/delete`)

The SQLite table is auto-created on first request. See `app/Controller/NoteController.php` for the full implementation.

---

## Requirements

- PHP 8.1+
- Composer
- Docker (optional, for `josix:live`)

---

## License

MIT
