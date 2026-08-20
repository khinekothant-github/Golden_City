---
paths:
  - 'app/Observers/**'
---

# Observers

## Observer pattern for real-time/domain side effects
Real-time and model lifecycle side effects (broadcasting, event dispatch, cache invalidation, notifications) go in Laravel model observers under app/Observers. Register observers in a service provider. Do not put side effects inside repositories — repositories only persist; observers react.
