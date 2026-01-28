# ⚽ Soccer Tournament Management System

A comprehensive **software architecture comparison project** implementing a team-based soccer tournament management system using both **Monolithic** and **Distributed (Microservices)** architectures.

This project is designed to analyze, compare, and evaluate architectural trade-offs in real-world web applications.

---

## 🎯 Project Overview

The Soccer Tournament Management System manages the **complete lifecycle of soccer tournaments**, including:

- 🏆 Tournament creation and management  
- 👥 Team and player registration  
- 📅 Match scheduling and management  
- ⚽ Real-time match event tracking  
- 📊 Results recording and standings calculation  
- 🌐 Public tournament information dissemination  

---

## 🏗️ Architectural Comparison

### 🔹 Monolithic Architecture (`/monolith`)

- Single deployable application
- Unified codebase and database
- Simplified development and deployment
- Tight coupling between components
- Easier debugging, limited scalability

**Best suited for:** small to medium-scale systems and rapid development.

---

### 🔹 Distributed Architecture (`/distributed`)

A microservices-based system composed of **6 independent services**:

| Service | Port | Responsibility |
|------|------|---------------|
| 🔐 auth-service | 8001 | Authentication & authorization |
| 🏆 tournament-service | 8002 | Tournament management |
| 👥 team-service | 8003 | Team & player management |
| ⚽ match-service | 8004 | Match scheduling & live events |
| 📊 results-service | 8005 | Results, standings & statistics |
| 🌉 gateway-service | 8000 | API Gateway & request routing |

**Key Characteristics**
- Independent deployment & scaling
- Service isolation & fault tolerance
- REST-based inter-service communication
- Increased operational complexity

**Best suited for:** large-scale, scalable, and resilient systems.

---

## 🛠️ Technology Stack

- **Backend:** Laravel (PHP Framework)
- **Database:** MySQL
- **Containerization:** Docker & Docker Compose
- **Communication:** REST APIs (HTTP / JSON)
- **Frontend:** Blade Templates + Tailwind CSS
- **Testing:** PHPUnit & Postman Collections

---

## 🚀 Quick Start

### ▶ Monolithic Version

```bash
cd monolith
docker-compose up --build
```

Copy code
http://localhost:8000
▶ Distributed Version
bash
Copy code
cd distributed
docker-compose up --build
API Gateway Access:

arduino
Copy code
http://localhost:8000
📚 Features
✅ Core Functionality
User authentication & role-based access control

Tournament creation with customizable settings

Team registration with player management

Automated match scheduling

Real-time match event tracking (goals, cards, substitutions)

Live standings and statistics

Public-facing tournament website

👥 User Roles
👤 Public Users – View tournaments, teams, and matches

👨‍💼 Administrators – Full system management

🏃 Coaches – Manage assigned teams and players

🥅 Referees – Record match events and results

🧪 Testing
Both architectures include comprehensive testing:

Unit Tests (PHPUnit)

Feature Tests

API Tests (Postman Collections)

Integration Tests

📖 Documentation
Architecture diagrams and design decisions

API documentation for all services

Setup and deployment guides

Performance comparison metrics

Architectural trade-off analysis

🔍 Research Focus
This project evaluates Monolithic vs Microservices architectures with emphasis on:

Scalability and performance

Development complexity vs operational simplicity

Fault tolerance and reliability

Deployment strategies and maintainability

Cost and infrastructure implications

🎓 Academic Information
Course: Software Architectures (CM0639-1)

University: Ca' Foscari University of Venice

Student: Yared Debela

Student ID: 913882

Instructor: Prof. Pietro Ferrara

Academic Year: 2025–2026

📄 License
This project uses only open-source technologies and is developed strictly for educational purposes.
