---
paths:
  - 'app/Repositories/**'
---

# Repositories

## Repository pattern is mandatory for all data access
All DB/query logic lives in app/Repositories/*Repository classes. Controllers call repositories — never Eloquent/Model queries directly. Keep repositories focused on persistence/retrieval; business logic stays in controllers/services. One repository per aggregate, name it after the model it serves (e.g. UserRepository).
