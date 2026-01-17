# Team-Based Soccer Tournament Management System
## Software Architectures – Final Project

**Course:** Software Architectures (CM0639-1)  
**University:** Ca' Foscari University of Venice  
**Department:** Environmental Sciences, Informatics and Statistics  
**Academic Year:** 2025–2026  

**Student Name:** Yared Debela  
**Student ID:** 913882  
**Instructor:** Prof. Pietro Ferrara  

---

## Project Overview

This project implements an IT system for managing team-based soccer tournaments.
The system supports the full tournament lifecycle, including team and player
registration, match scheduling, match event tracking, result recording, standings
calculation, and public dissemination of tournament information.

The main objective of the project is to **design, implement, and compare two different
software architectures** that realize the same functional and non-functional requirements:

- **Monolithic Architecture**
- **Distributed (Service-Based) Architecture**

This comparison allows an evaluation of architectural trade-offs related to scalability,
performance, reliability, deployability, maintainability, and cost.

---

## Project Organization

This project was developed by a **single student as an individual assignment**.
All architectural design, implementation, documentation, and deployment activities
were performed by the same author.

The Git history reflects this individual contribution through incremental,
feature-based commits.

---

## Architectural Approaches

### 1. Monolithic Architecture
The monolithic implementation is developed and deployed as a single application.
It contains all business logic, data access, and user interfaces within one codebase
and uses a single relational database.

### 2. Distributed Architecture
The distributed implementation decomposes the system into multiple independent
services, each responsible for a specific domain concern. Services communicate
through REST APIs and can be deployed and scaled independently.

Both implementations satisfy the same requirements and expose equivalent system
behavior.

---

## Repository Structure

- `monolith/`  
  Contains the monolithic implementation of the system.

- `distributed/`  
  Contains the service-based distributed implementation.

- `docs/`  
  Contains architectural documentation and diagrams.

---

## Technologies Used

- Backend Framework: Laravel (PHP)
- Database: MySQL
- Containerization: Docker & Docker Compose
- Communication (Distributed): REST / HTTP / JSON

Only open-source technologies are used.

---

## How to Run the System

### Monolithic Architecture
```bash
cd monolith
docker-compose up --build
