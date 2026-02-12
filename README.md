# ⚽ Soccer Tournament Management System

A comprehensive **software architecture project** implementing a soccer tournament management system using both **Monolithic** and **Distributed (Microservices)** architectures.

This project demonstrates implementation of architectural patterns for a software architecture course, complete with full features, testing, and documentation.

---

## 🎯 Project Overview

The Soccer Tournament Management System manages the **complete lifecycle of soccer tournaments**, including:

- 🏆 **Tournament Creation & Management**: Customizable tournament formats, scheduling, and configuration
- 👥 **Team & Player Registration**: Complete team management with player rosters and validation
- 📅 **Match Scheduling**: Automated fixture generation with venue management
- ⚽ **Real-time Match Events**: Live scoring, cards, substitutions, and match events
- 📊 **Results & Standings**: Automatic standings calculation and statistical analysis
- 🌐 **Public Information**: Public-facing website for tournament information and updates
- 🔐 **Multi-Role Access**: Role-based access control for administrators, coaches, and referees
- 🔍 **Advanced Search**: Global search across tournaments, teams, players, and matches

## 🏗️ Architectural Implementation

### 🔹 Monolithic Architecture (`/monolith`)

**Traditional single-application implementation**

- **Single Deployable Unit**: All functionality in one Laravel application
- **Unified Database**: MySQL database with shared schema
- **In-process Communication**: Direct method calls between components
- **Shared Technology Stack**: Laravel, Blade templates, Tailwind CSS

**Implementation Characteristics:**
- **Codebase Size**: ~15,000 lines of PHP code
- **Database Tables**: 25+ interconnected tables
- **Authentication**: Laravel Passport with OAuth2
- **Frontend**: Blade templates with Tailwind CSS

**Implementation Status:** ✅ **Complete** - Full tournament management system

---

### 🔹 Distributed Architecture (`/distributed`)

**Microservices implementation with service separation**

| Service | Port | Technology | Responsibility |
|---------|------|------------|---------------|
| 🔐 **auth-service** | 8001 | Laravel + Passport | Authentication, authorization, user management |
| 🏆 **tournament-service** | 8002 | Laravel | Tournament, sport, and venue management |
| 👥 **team-service** | 8003 | Laravel | Team and player registration with validation |
| ⚽ **match-service** | 8004 | Laravel | Match scheduling and live event tracking |
| 📊 **results-service** | 8005 | Laravel | Results processing, standings, and statistics |
| 🌉 **gateway-service** | 8000 | Laravel | API Gateway and public API aggregation |
| 🖥️ **admin-dashboard** | 3000 | React + Vite | Administrative interface for system management |
| 🌐 **public-view** | 3001 | React + Vite | Public-facing tournament website |

**Implementation Characteristics:**
- **Service Independence**: Each service has own database and deployment
- **Inter-service Communication**: REST APIs with Redis pub/sub for events
- **Data Separation**: Separate MySQL databases per service
- **Frontend Applications**: React SPAs with modern UI/UX

**Implementation Status:** ✅ **Complete** - Production-ready microservices

---

## 🛠️ Technology Stack

### Backend Implementation
- **Framework:** Laravel 11 (PHP 8.2+)
- **Database:** MySQL 8.0 with service-specific databases
- **Authentication:** Laravel Passport (OAuth2)
- **Queue System:** Redis with Laravel Queues
- **Event System:** Redis Pub/Sub for inter-service communication
- **Containerization:** Docker & Docker Compose

### Frontend Implementation
- **Admin Dashboard:** React 18 + Vite + Tailwind CSS + React Query
- **Public View:** React 18 + Vite + Tailwind CSS + Framer Motion + Recharts
- **Monolith UI:** Blade Templates + Tailwind CSS

### Development Tools
- **Testing:** PHPUnit (Backend), React Testing Library (Frontend)
- **API Testing:** Comprehensive Postman collections
- **Code Quality:** ESLint, PHP CS Fixer
- **Documentation:** Markdown with implementation guides

---

## 🚀 Quick Start

### Prerequisites
- **Docker & Docker Compose** (recommended)
- **Node.js 18+** (for frontend development)
- **PHP 8.2+** (for backend development)
- **MySQL 8.0+** (if not using Docker)
- **Redis 6.0+** (for queues and events)

### ▶ Monolithic Version

**Using Docker (Recommended):**
```bash
cd monolith
docker-compose up --build
```

**Manual Setup:**
```bash
cd monolith
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

🌐 **Access:** http://localhost:8000

### ▶ Distributed Version

**Using Docker (Recommended):**
```bash
cd distributed
docker-compose up --build
```

**Manual Setup:**
```bash
# Start infrastructure services
docker-compose up -d mysql redis

# Setup each service (see individual service READMEs)
cd auth-service && composer install && php artisan migrate --seed && php artisan passport:install
# Repeat for other services...

# Start frontend applications
cd admin-dashboard && npm install && npm run dev
cd public-view && npm install && npm run dev
```

🌐 **Access Points:**
- **API Gateway:** http://localhost:8000
- **Admin Dashboard:** http://localhost:3000
- **Public View:** http://localhost:3001
- **Individual Services:** http://localhost:8001-8005
## 📚 Course Information

### Project Details
- **Course:** Software Architectures (CM0639-1)
- **University:** Ca' Foscari University of Venice
- **Academic Year:** 2025–2026
- **Instructor:** Prof. Pietro Ferrara
- **Student:** Yared Debela (ID: 913882)

### Learning Objectives
This project demonstrates understanding of:
- **Architectural Patterns**: Monolithic vs. Microservices implementation
- **Service Design**: Separation of concerns and business capabilities
- **Data Management**: Database design and inter-service communication
- **Technology Integration**: Modern frameworks and tools
- **System Integration**: End-to-end application development

---

## 📄 License & Usage

This project is developed for **educational purposes** to demonstrate software architecture concepts.

### Educational Use
- ✅ **Architecture Reference**: Implementation patterns for study
- ✅ **Learning Resource**: Complete system for courses
- ✅ **Code Examples**: Real-world implementation techniques
- ✅ **Documentation**: Comprehensive guides and setup instructions

### Open Source Technologies Used
- **Laravel Framework** (MIT License)
- **React** (MIT License)
- **Docker** (Apache License 2.0)
- **MySQL** (GPL License)
- **Redis** (BSD License)
- **Tailwind CSS** (MIT License)

---

**🎯 Project Status:** ✅ **Complete** - Both architectures fully implemented

**📊 Implementation Coverage:** 100% of planned features across both monolithic and distributed architectures
