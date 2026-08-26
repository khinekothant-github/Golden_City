# Golden City — Database Spec

> **Draft v4** — iterate & finalize here before migrations are written.
> Hierarchy: Phase → Building → Unit. Sales data split from Unit inventory.
> Customers are created at appointment booking and enriched after the visit.
>
> **v3 → v4:** appointments decision documented (attribute, not entity — see
> Design decisions); `units` may hang directly off a phase (`building_id`
> nullable + `phase_id`); `sales.promotion_remark` added; `channel_report`
> dropped (`sources` owns attribution); `number_of_visit` → `number_of_visits`;
> indexes & uniques locked down; open questions closed where decided.

## Core rule

**No unit FKs on `customers` — ever.** Every customer↔unit link goes through a
pivot (`customer_unit_interests`) or `sales`. A customer can prefer many units,
give feedback on many units, and buy many units.

## Lifecycle flow

```
Appointment booked  → customer row created (identity, type, appointment_time)
                    → customer_sale_person rows
                    → customer_unit_interests rows (sentiment = Preferred)
After visit         → number_of_visits++
                    → customer_unit_interests rows (Liked / Disliked + comments)
                    → sources row (category / sub_category / agency / event)
Booking             → sales row (process_type = Booking)
Final sale          → sales row (process_type = Sale) — full history preserved
```

`ponytail:` appointment stays an attribute until a revisit trigger fires —
full reasoning + ready-made sketch under **Design decisions** below.

## Design decisions

### No `appointments` table — on purpose

An entity deserves its own table only with independent identity: multiplicity,
its own state machine, or inbound references. The appointment has none.

- **Born together, 1:1** — the customer row is created at booking. The
  appointment is the customer's creation event, so it is stored as an
  attribute (`customers.appointment_time`).
- **No lifecycle of its own** — nothing tracks Scheduled / Confirmed /
  No-show / Cancelled. Attendance is already captured by `number_of_visits`.
- **Nothing points at it** — sale persons attach to the customer
  (`customer_sale_person`), feedback attaches to customer+unit
  (`customer_unit_interests`), attribution attaches to the customer
  (`sources`), money attaches to customer+unit (`sales`). An `appointments`
  table would be an orphan duplicating `appointment_time`, forcing a JOIN
  into every query for zero information gain.

Splitting customers into "before / after appointment" tables is the failure
mode this avoids: one person fragmenting into two rows makes every FK
(`sales.customer_id`, `sources.customer_id`) ambiguous. One `customers` row
that fills in over time is the model.

**Revisit triggers — build the table the moment ANY becomes a requirement:**

- appointment statuses (confirmed / attended / no-show / cancelled)
- reschedule history must survive
- a customer holds multiple future bookings
- per-visit staff assignment or per-visit reminders

Sketch for that day (extraction stays additive):

```
appointments
  id                  bigint PK
  customer_id         bigint FK -> customers, index
  scheduled_at        timestamp required
  status              string (Scheduled / Confirmed / Attended / NoShow / Cancelled)
  rescheduled_from_id bigint nullable FK -> appointments
  notes               text nullable
  timestamps
```

Backfill: `INSERT INTO appointments (customer_id, scheduled_at)
SELECT id, appointment_time FROM customers;` — then stop writing
`customers.appointment_time`.

### Units may hang directly off a Phase

Landed plots / shop offices can sit in a phase with no building, so a unit's
parent is `building_id` **or** `phase_id` — never both, never neither. If it
is confirmed that every unit sits inside a building, tighten back to
`building_id NOT NULL` and drop `phase_id`.

---

## phases
| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint | PK | |
| name | string | required | |
| location | string | nullable | |
| type | string | default residential | enum: Residential / Commercial / Mixed |
| timestamps / soft_deletes | | | |

## buildings
| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint | PK | |
| phase_id | bigint | FK -> phases, index | |
| name | string | required | |
| building_type | string | nullable | |
| timestamps / soft_deletes | | | |

unit_count: derived — `Building::withCount('units')`. Not stored.

## units
| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint | PK | |
| phase_id | bigint | FK -> phases, nullable, index | parent for units with no building (landed plots) |
| building_id | bigint | FK -> buildings, nullable, index | null ⇒ unit belongs straight to a phase |
| name | string | required | unit number/name |
| type | string | nullable | enum TBD: e.g. Studio / 1BR / 2BR / Shop |
| room_description | text | nullable | |
| base_price | decimal(12,2) | default 0 | list price; deal price lives on sales |
| status | string | default available | enum: Available / Reserved / Sold |
| timestamps / soft_deletes | | | |

Parent rule: exactly one of `building_id` / `phase_id`. Enforce with app-side
validation plus a DB CHECK `(building_id IS NULL) <> (phase_id IS NULL)`.
Name uniqueness is scoped to the parent: partial UNIQUE `(building_id, name)
WHERE building_id IS NOT NULL` and `(phase_id, name) WHERE phase_id IS NOT NULL`.

## customers
| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint | PK | |
| name | string | required | |
| email | string | nullable | |
| contact_number | string | unique | dedupes walk-ins |
| type | string | required | enum: New / Old |
| appointment_time | timestamp | nullable, index | set when appointment is booked — see Design decisions |
| number_of_visits | integer | default 0 | incremented after each visit |
| purchase_purpose | string | nullable | enum: OwnStay / Investment |
| timestamps / soft_deletes | | | |

v4: `channel_report` removed — `sources.category` / `sub_category` own
attribution; two parallel mechanisms would fork every report. Revisit only if
Richard means something distinct by it.

## customer_sale_person (pivot)
| Column | Type | Constraints | Notes |
|---|---|---|---|
| customer_id | bigint | FK -> customers | |
| sale_person_id | bigint | FK -> users | many sale persons per customer |
| | | UNIQUE (customer_id, sale_person_id) | |

## customer_unit_interests (pivot)
Covers `prefer_unit`, `most_like_points`, `most_dislike_points` in one table.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint | PK | |
| customer_id | bigint | FK -> customers, index | |
| unit_id | bigint | FK -> units, index | |
| sentiment | string | required | enum: Preferred / Liked / Disliked |
| comment | text | nullable | the "points" |
| | | UNIQUE (customer_id, unit_id, sentiment) | |

## sources
Single attribution mechanism — owns everything "where did this customer come
from" (supersedes the dropped `channel_report`).

| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint | PK | |
| customer_id | bigint | FK -> customers, UNIQUE | one source per customer |
| category | string | required | |
| sub_category | string | nullable | |
| agency_id | bigint | FK -> agencies, nullable | |
| event_id | bigint | FK -> events, nullable | |
| timestamps | | | |

## agencies
| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint | PK | |
| name | string | required | |
| staff | string | nullable | contact person at the agency |
| commission_id | bigint | FK -> commission_schemes, nullable | default scheme |
| phone | string | nullable | agency phone |
| sale_staff_phone | string | nullable | |
| timestamps / soft_deletes | | | |

## commission_schemes
| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint | PK | |
| name | string | required | |
| rate | decimal(5,2) | nullable | |
| timestamps | | | |

## events
| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint | PK | |
| name | string | required | |
| starts_at | timestamp | required | |
| ends_at | timestamp | nullable | |
| timestamps | | | |

## sales
| Column | Type | Constraints | Notes |
|---|---|---|---|
| id | bigint | PK | |
| unit_id | bigint | FK -> units, index | multiple rows per unit = resale history |
| customer_id | bigint | FK -> customers, index | NOT unique — one customer, many units |
| sale_person_id | bigint | FK -> users, nullable | |
| agency_id | bigint | FK -> agencies, nullable | |
| commission_id | bigint | FK -> commission_schemes, nullable | overrides agency default |
| event_id | bigint | FK -> events, nullable | promo event that sourced the sale |
| promotion_remark | text | nullable | deal-specific promo note (was wrongly drafted on Unit) |
| price | decimal(12,2) | required | actual deal price |
| payment_plan | string | nullable | enum TBD; structured installments stay Deferred |
| process_type | string | nullable | enum TBD: e.g. Booking / Reservation / Sale |
| sold_at | timestamp | nullable | |
| timestamps / soft_deletes | | | |

---

## Open questions (for Richard)
1. Any units without a building (landed plots / shop offices)? Decides whether
   `units.building_id` tightens back to NOT NULL and `phase_id` goes away.
2. Unit types — fixed list? (Studio / 1BR / 2BR / Penthouse / Shop / Land?)
3. `payment_plan` values — which plans exist in practice? (structure stays
   Deferred regardless)

## Resolved in v4
- `booking_unit` → covered by `sales` (unit_id + customer_id + process_type)
- `channel_report` → dropped; `sources` owns attribution
- agency `staff` → contact-person name (string)
- multiple appointments per customer → deferred deliberately; triggers +
  sketch in Design decisions
- unit name uniqueness → yes, scoped per parent (see units)

## Deferred
- `appointments` table — sketch + revisit triggers in Design decisions
- channels lookup table (only if source categories outgrow free strings)
- installments / payment schedule table
- unit media / floor plans
