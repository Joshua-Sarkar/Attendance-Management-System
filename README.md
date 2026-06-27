# AMS-V1 — Attendance Management System

A production-ready workforce management platform built with Laravel 12, designed to manage employees, attendance, leave requests, departmental hierarchy, and workforce operations from a single centralized dashboard.

---

## Overview

AMS-V1 is a full-stack Human Resource and Attendance Management System developed to replace spreadsheet-driven workforce operations and reduce dependency on third-party HR platforms.

The system provides:

* Employee Management
* Department Management
* Role-Based Access Control
* Attendance Tracking
* Leave Management
* Manager Hierarchy
* Workforce Monitoring
* Employee Profile Management
* Bulk Employee Import from Zimyo Exports
* Production Deployment on Hostinger

Built using Laravel, TailwindCSS, Breeze Authentication, MySQL, and GitHub-based deployment workflows.

---

## Features

### Authentication & Security

* Secure authentication using Laravel Breeze
* Role-based access control
* Admin, Manager, and Employee roles
* Temporary password provisioning
* Mandatory password change on first login
* Encrypted sensitive employee information

---

### Employee Management

* Create, edit, and manage employees
* Auto-generated employee IDs
* Department assignment
* Reporting manager hierarchy
* Employee profile management
* Workforce status tracking

---

### Attendance Management

* Employee check-in/check-out
* Daily attendance records
* Attendance status monitoring
* Present, Late, Absent, Leave, and Work From Home tracking
* Attendance analytics and dashboard metrics

---

### Leave Management

* Employee leave requests
* Manager/Admin approval workflow
* Leave history tracking
* Leave status management
* Attendance override business rules

---

### Workforce Hierarchy

* Department structure
* Reporting managers
* Manager-specific employee visibility
* Workforce monitoring by hierarchy

---

### Employee Profile System

Employee profiles support:

* Personal Information
* Contact Details
* Employment Details
* Government Identification
* Banking Information
* Emergency Contacts

Sensitive information is stored securely using encryption.

---

### Zimyo Migration Engine

Built-in import pipeline for migrating workforce data from Zimyo exports.

Capabilities include:

* Bulk employee creation
* Department creation
* Manager hierarchy mapping
* Employee profile generation
* Temporary credential generation
* Duplicate prevention
* Update existing employee records

Successfully tested against a real-world workforce dataset.

---

## Technology Stack

### Backend

* Laravel 12
* PHP 8+
* MySQL
* Eloquent ORM

### Frontend

* Blade
* Tailwind CSS
* Laravel Breeze

### DevOps

* Git
* GitHub
* Hostinger / cPanel Deployment
* Composer
* Vite

---

## User Roles

### Admin

* Full system access
* Manage workforce
* Approve leave requests
* Import employees
* Manage departments

### Manager

* View assigned employees
* Approve employee leave requests
* Monitor attendance

### Employee

* Check In / Check Out
* Submit leave requests
* View attendance history
* Manage profile

---

## Current Project Status

### Completed

* Authentication & Onboarding Security (Forced password resets)
* Department & Workforce Ledger Directory
* Employee Tabbed Profiles & Dossier (AES-256 field encryption casts)
* Attendance Tracking & late delay calculations (09:00/09:30 transitions)
* Attendance Audit Center & log search dashboards
* Leave Management & Double-Entry Ledger (Accruals command & locks)
* Zimyo Excel Import Engine & Web Import Interface
* Profile Correction Requests Queue with sidebar badges
* Production Configuration Hardening (caching compliance)
* Automated Pest/PHPUnit Test Suite (103 tests, 548 assertions passing)

### Planned

* Phase 5 — Payroll Integration algorithms (unpaid hours and salary deductions calculations)

### Future

* Geofencing and biometric checking integrations
* Two-Factor Authentication (2FA) and OAuth2 logins
* PDF report exports for attendance audits
* Document scan uploads (e.g. PDF attachments for Aadhaar/PAN)

---

## Deployment

Production environment is deployed using:

GitHub → Hostinger/cPanel → Laravel Application

Changes are version controlled and deployed through Git workflows.

---

## Project Philosophy

AMS-V1 was built with a simple goal:

> Own the workforce data.
>
> Remove spreadsheet chaos.
>
> Replace repetitive HR operations with structured systems.

This project evolved from a basic attendance tracker into a scalable Human Resource Management platform capable of supporting real-world organizational workflows.

---

## Author

Joshua Sarkar

Built with Laravel, stubbornness, caffeine, Git commits, production bugs, and far too many terminal sessions.
