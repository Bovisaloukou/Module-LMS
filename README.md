# Module LMS

A full-featured Learning Management System built with Laravel 12, Filament 3, and Livewire 3.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.4
- **Admin & Instructor Panels:** Filament 3
- **Student Frontend:** Livewire 3, Tailwind CSS 4
- **Authentication:** Laravel Sanctum (API tokens + web sessions)
- **Payments:** Stripe (Payment Intents + webhooks)
- **Media:** Spatie Media Library
- **Roles & Permissions:** Spatie Laravel Permission
- **Certificates:** DomPDF (auto-generated on course completion)
- **API Docs:** Scribe
- **Testing:** PHPUnit (164 tests, 457 assertions)
- **CI/CD:** GitHub Actions

## Features

### Student Frontend (Livewire)
- Course catalog with search, category/level filters, and sorting
- Course detail pages with curriculum preview, reviews, and enrollment
- Learning experience with sidebar curriculum, lesson viewer, and progress tracking
- Quiz system with multiple question types and auto-grading
- Certificate generation and PDF download on course completion
- Student dashboard with enrollment progress overview

### RESTful API (Sanctum)
- Authentication (register, login, logout)
- Course browsing with filtering and pagination
- Enrollment with Stripe payment integration
- Progress tracking (lesson completion, watch time)
- Quiz attempts with 4 question types (single choice, multiple choice, true/false, short answer)
- Certificate verification (public endpoint)
- Reviews and discussions with Q&A

### Admin Panel (Filament)
- Full CRUD for courses, modules, lessons, quizzes
- User management with role assignment
- Review moderation (approve/reject)
- Certificate management (read-only)

### Instructor Panel (Filament)
- Course management scoped to own courses
- Module and lesson management via relation managers
- Quiz creation with nested questions and answers
- Dashboard with stats (courses, students, revenue, ratings)
- Submit courses for review (admin approval flow)

## Roles

| Role | Access |
|------|--------|
| **Admin** | Full access to everything via admin panel |
| **Instructor** | Manages own courses, modules, lessons, quizzes via instructor panel |
| **Student** | Browses courses, enrolls, learns, takes quizzes, earns certificates |

## Getting Started

### Prerequisites

- PHP 8.2+
- MySQL 8.0+ or MariaDB 10.5+
- Composer
- Node.js 18+
- Stripe account (for payments)

### Installation

```bash
# Clone the repository
git clone https://github.com/Bovisaloukou/Module-LMS.git
cd Module-LMS

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate
```

Configure your `.env` file:

```env
DB_DATABASE=module_lms
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

```bash
# Run migrations and seed demo data
php artisan migrate:fresh --seed

# Build frontend assets
npm run build

# Start the server
php artisan serve
```

### Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@lms.test | password |
| Instructor | instructor@lms.test | password |
| Student | student@lms.test | password |

### URLs

| Page | URL |
|------|-----|
| Student Frontend | http://localhost:8000 |
| Admin Panel | http://localhost:8000/admin |
| Instructor Panel | http://localhost:8000/instructor |
| API Documentation | http://localhost:8000/docs |

## Development

```bash
# Start all services (server, queue, logs, vite)
composer dev

# Run tests
php artisan test

# Run a specific test
php artisan test --filter=CourseShowTest

# Fix code style
vendor/bin/pint

# Check code style without fixing
vendor/bin/pint --test

# Generate API documentation
php artisan scribe:generate

# Reset database with demo data
php artisan migrate:fresh --seed
```

## API Overview

All API endpoints are prefixed with `/api`. Authentication uses Bearer tokens via Sanctum.

### Public Endpoints
- `POST /api/register` - Create account
- `POST /api/login` - Get auth token
- `GET /api/courses` - Browse published courses
- `GET /api/courses/{slug}` - Course details
- `GET /api/categories` - List categories
- `GET /api/certificates/{number}/verify` - Verify certificate

### Student Endpoints (authenticated)
- `POST /api/courses/{id}/enroll` - Enroll in course
- `GET /api/courses/{id}/progress` - View progress
- `POST /api/lessons/{id}/complete` - Mark lesson complete
- `GET /api/quizzes/{id}` - View quiz
- `POST /api/quizzes/{id}/start` - Start attempt
- `GET /api/certificates` - List my certificates

### Instructor Endpoints (authenticated, instructor/admin role)
- `GET /api/instructor/dashboard` - Stats overview
- `GET /api/instructor/courses` - My courses
- `POST /api/instructor/courses` - Create course
- `POST /api/instructor/courses/{id}/quizzes` - Create quiz

Full API documentation available at `/docs` after running `php artisan scribe:generate`.

## Architecture

```
app/
├── Enums/          # 7 backed string enums with label() and color()
├── Filament/
│   ├── Admin/      # Admin panel resources
│   └── Instructor/ # Instructor panel resources (scoped queries)
├── Http/
│   ├── Controllers/Api/   # RESTful API controllers with Scribe docblocks
│   ├── Requests/          # Form requests organized by domain
│   └── Resources/         # API resources with conditional relationships
├── Livewire/       # 9 full-page Livewire components
│   ├── Auth/       # Login, Register
│   └── Learning/   # CourseLearn, QuizAttempt
├── Models/         # Eloquent models with relationships and scopes
├── Policies/       # Authorization (role + ownership checks)
└── Services/       # 6 service classes for business logic
```

## Testing

The project has **164 tests** and **457 assertions** covering:

- API authentication (register, login, logout)
- Course CRUD and browsing
- Enrollment and payment flows
- Progress tracking and auto-completion
- Quiz attempts and auto-grading
- Certificate generation and verification
- Reviews and discussions
- Instructor course/quiz management
- Dashboard stats
- Livewire components (auth, catalog, course detail, dashboard, learning)

```bash
php artisan test
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
