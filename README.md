
⚽ Soccer Tournament Management System
A comprehensive software architecture comparison project implementing a team-based soccer tournament management system in both monolithic and distributed (microservices) architectures.

🎯 Project Overview
This system manages the complete lifecycle of soccer tournaments including:

🏆 Tournament creation and management
👥 Team and player registration
📅 Match scheduling and management
⚽ Real-time match event tracking
📊 Results recording and standings calculation
🌐 Public tournament information dissemination
🏗️ Architectural Comparison
Monolithic Architecture (/monolith)
Single deployable unit with all functionality in one codebase
Unified database for all data operations
Simplified deployment and development workflow
Tight coupling between components
Distributed Architecture (/distributed)
6 microservices handling specific domains:
🔐 auth-service (8001) - Authentication & authorization
🏆 tournament-service (8002) - Tournament management
👥 team-service (8003) - Team & player management
⚽ match-service (8004) - Match scheduling & events
📊 results-service (8005) - Results & statistics
🌉 gateway-service (8000) - API Gateway & load balancing
Independent deployment and scaling
Service isolation and fault tolerance
Inter-service communication via REST APIs
🛠️ Technology Stack
Backend: Laravel (PHP Framework)
Database: MySQL
Containerization: Docker & Docker Compose
Communication: REST APIs / HTTP / JSON
Frontend: Blade Templates with Tailwind CSS
Testing: PHPUnit + Postman Collections
🚀 Quick Start
Monolithic Version
bash
cd monolith
docker-compose up --build
# Access: http://localhost:8000
Distributed Version
bash
cd distributed
docker-compose up --build
# API Gateway: http://localhost:8000
📚 Features
Core Functionality
✅ User authentication and role-based access control
✅ Tournament creation with customizable settings
✅ Team registration with player management
✅ Automated match scheduling
✅ Real-time match event tracking (goals, cards, substitutions)
✅ Live standings and statistics
✅ Public-facing tournament website
User Roles
👤 Public Users - View tournaments, teams, matches
👨‍💼 Administrators - Full system management
🏃 Coaches - Manage assigned teams and players
🥅 Referees - Record match events and results
🧪 Testing
Each architecture includes comprehensive testing:

Unit Tests (PHPUnit)
Feature Tests
API Tests (Postman Collections)
Integration Tests
📖 Documentation
Architecture diagrams and design decisions
API documentation for all services
Setup guides for both architectures
Performance comparison metrics

🎓 Academic Project
Course: Software Architectures (CM0639-1)
University: Ca' Foscari University of Venice
Student: Yared Debela (ID: 913882)
Instructor: Prof. Pietro Ferrara
Academic Year: 2025-2026

🔍 Research Focus
This project demonstrates and evaluates architectural trade-offs between monolithic and distributed systems:

Scalability patterns and performance
Development complexity vs operational simplicity
Fault tolerance and system reliability
Deployment strategies and maintainability
Cost implications of different approaches
📄 License
This project uses only open-source technologies and is developed for educational purposes.

Perfect for: Students learning software architecture, developers comparing system designs, or anyone interested in microservices vs monolithic architectures!
