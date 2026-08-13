# DPS Millennium International School — Homepage Design Spec

**Date:** 2026-08-06
**Status:** Approved (direction C — "The Seal")
**Scope:** Prototype homepage only. Static Next.js export, no backend.

---

## 1. Context

DPS Millennium International School is a new CBSE school in Al Wukair, Qatar, part of the
DPS Group of Institutions. **Tentative opening: 4 April 2027.** Construction is underway.

> **Copy constraint — the date is TENTATIVE.** Never phrase it as a flat assertion
> ("On 4 April 2027 it opens"). A parent makes an enrolment decision on this date; if it
> slips, an unhedged claim becomes a complaint. Approved phrasings: "Opening April 2027",
> "Doors open 4 April 2027 (target)", "Tentative opening: 4 April 2027".

There is no campus to photograph, no staff, no students. The current dpsmillennium.com is a
GoDaddy Website Builder holding page carrying a Getty stock photo.

**Primary audience:** parents in Qatar, mostly Indian expat families, choosing a school for
April 2027 enrolment. Majority will view on a mid-range Android phone at ~360px.
**Secondary audience:** the school board and the Ministry.

**The single job of this homepage:** make a parent believe this school is real, serious, and
worth registering interest in, eighteen months before it opens.

**Secondary job:** win an internal argument about not building the production site on a
website builder.

---

## 2. Design direction

Selected from three candidates (A — The Countdown, B — The Blueprint, C — The Seal).

**C — The Seal.** The school's own crest is the signature moment and the structural source
for the page.

### 2.1 The signature element: "The Assembly"

The seal loads **incomplete** and assembles itself: outer rings struck first, then the four
quadrants fill. It happens **once, on load, and never again on the page.**

Rationale: the school is an institution assembling itself in public. The mark does the same
thing. This states the thesis through motion instead of asserting it in a headline, and it
is the one place boldness is spent. Everything around it stays quiet.

**Hard constraints on the Assembly:**

- The **completed** seal is the default state in the markup. The animation is progressive
  enhancement only.
- Nothing on the page requires JavaScript to become visible.
- Under `prefers-reduced-motion: reduce`, the finished seal renders instantly and no content,
  meaning, or affordance is lost.
- Animates `transform` and `opacity` only. Target 60fps on a mid-range Android under CPU
  throttling.

---

## 3. Token system

### 3.1 Colour

Five working values. Hierarchy is enforced, not suggested — six colours at equal weight is
how the page becomes a circus.

| Token | Hex | Contrast on paper | Job |
|---|---|---|---|
| `--paper` | `#F5F2F7` | — | Page ground. Faint lilac-grey derived from the purple at very low saturation. Deliberately not white, and not the cream-paper default. |
| `--ink` | `#1B0A26` | — | Deep purple-black. Inverted sections and footer. |
| `--purple` | `#400068` | 14.8:1 on white | Carries the page. Body text, headings, primary UI. |
| `--green` | `#206838` | 6.8:1 on white | Supports. Secondary text, section keys. |
| `--gold` | `#D8A800` | 2.2:1 on white | Punctuates. Rules, fills, and text **on ink only** (8.2:1). |

**Rare, effectively unused as UI:**

| Token | Hex | Rule |
|---|---|---|
| `--indigo` | `#382898` | Quadrant colour and rare deep accent. |
| `--red` | `#D82818` | Quadrant colour only. **Never** promoted to UI — it reads as alert/error. |

**Absolute rules:**

- Gold is never text on paper or on white.
- Red never becomes a brand workhorse.

### 3.2 Type

One superfamily. Institutional, RTL-ready, and deliberately not Inter or Poppins.

| Role | Family |
|---|---|
| Display | IBM Plex Serif |
| Body / UI | IBM Plex Sans |
| Arabic (all roles) | IBM Plex Sans Arabic |

Rationale: IBM Plex Sans Arabic is a genuine companion from the same foundry, designed
alongside the Latin. Phase 2 Arabic localisation becomes a swap, not a rebuild. Plex also
carries proper tabular figures, which the opening date and any countdown need.

**Delivery:** self-hosted, subset, variable axes where available. **No Google Fonts CDN
call** — third-party asset requests are banned (see §6).

### 3.3 Bilingual / RTL

Full Arabic localisation is Phase 2, but built for from day one:

- No hardcoded strings in components. All copy from config.
- No directional assumptions in CSS. Use logical properties (`margin-inline-start`,
  `padding-inline`, `inset-inline`) throughout — never `left`/`right`.
- `dir` attribute drives layout; nothing assumes LTR.

---

## 4. Logo lockup system

The full seal has a double outer ring, fine quadrant detail, and text in two scripts. It
turns to mush below roughly 120px. It is therefore **two separate assets**, not one scaled
asset.

| Lockup | Where | Notes |
|---|---|---|
| **Full seal** | Hero, footer | Only at ≥120px rendered size. |
| **Shield mark** | Nav, favicon, mobile, anything <120px | Shield only — four quadrants, no rings, no bilingual text. |

Both as optimised SVG. Never scale the full seal down to nav size.

### 4.1 Quadrant meanings — resolved

Sampled from the supplied artwork (`WhatsApp Image 2026-08-05 at 8.22.05 PM.jpeg`):

| Position | Colour | Motif |
|---|---|---|
| Top-left | Gold | **Sail / architectural form.** *Not* an open book — the original brief assumed a book. Awaiting the school's official meaning. |
| Top-right | Red | Globe with atomic orbits |
| Bottom-left | Indigo | Waves |
| Bottom-right | Purple | Stars |

---

## 4A. Information architecture — this is a landing page, not a one-pager

Revised 2026-08-06 after client content notes. The homepage is a **hub**: it carries a small
amount of its own content and routes to everything else. It does **not** absorb About /
Fees / Contact into itself. That is precisely the mistake on the sister site.

### 4A.1 Homepage must carry

| Block | Notes |
|---|---|
| Crest / imagery | The Assembly (§2.1). Real photography when it exists. |
| Tentative opening date | Hedged per the copy constraint in §1. |
| Quick links | Routing to About, Fees, Contact, and the sister schools. The main job of the page. |
| Newsletter signup | Email capture. Needs a destination — see §9. |
| Registration of interest | Primary conversion. |

### 4A.2 Pages the homepage routes to (out of scope to build — links only)

| Page | Content the client has specified |
|---|---|
| About us | Mission · Vision · Key visionaries · Objectives · Founding story (why, when, how) |
| Contact us | Enquiry form · P.O. Box · Email · Phone |
| Fees structure | Overview. Model on the sister site's content, **not** its presentation. |

Links to unbuilt pages use the config-driven placeholder mechanism in §8 — named, and
hidden when unconfigured. No dead links shipped.

### 4A.3 Reference read — dpsmisdoha.com (structure only)

Reviewed at the client's request. The client does **not** endorse its design; it was
supplied to show how the content model works. Reviewed 2026-08-06.

**Their IA (worth taking):** `about-us/` (+ information, society, council, careers) ·
`dps-way/` (+ mission-and-vision, objective, quality-policy, academic-curriculum,
holistic-approach, technology-based-education, sports-and-activities) · `parent-corner/`
(+ fee-payment-schedule, online-payment-methods) · `contact-us/` · Publications ·
Information Corner.

**Faults to avoid — observed, not assumed:**

1. **Split-brain domain.** Public site is `dpsmisdoha.com`; content actually lives on
   `website.dpsmisdoha.com`. Two hosts for one school. Confusing and SEO-damaging.
2. **Two CMSs duct-taped together.** Legacy classic-ASP endpoints still serve live content
   under a WordPress front —
   `dpsmisdoha.com/DPSDoha/Userform/News/DisplayEvents.asp` and
   `.../DynamicFolder/Publications//Publications.html` (note the double slash).
3. **Principal's message as a homepage wall of text.** Several hundred words before a
   prospective parent reaches a single actionable fact.
4. **Images lazy-loaded behind empty SVG data-URI placeholders.** If the script fails, the
   page has no images at all. Violates our "nothing requires JS to become visible" rule.
5. **Fees buried under "Parent Corner."** A prospective parent is not yet a parent. The
   single most decision-critical page sits in the section written for people who already
   enrolled. **Our fees link goes in the top-level quick links.**
6. `user-scalable=no` blocking pinch-zoom (already flagged in §6).

**Verified fact worth using.** DPS-MIS is marking its Silver Jubilee — 25 years in Doha,
stated on their own homepage. That is a far stronger credibility anchor for §5 "The DPS
group" than anything we could assert ourselves, and it is citable.

---

## 5. Layout

```
┌─────────────────────────────────────────────────────────────┐
│ [shield]  DPS Millennium     About  Admissions  [Register]  │  shield mark only
├─────────────────────────────────────────────────────────────┤
│                                                             │
│            ╭──────────────╮                                 │
│            │   THE        │      A CBSE school              │
│            │   ASSEMBLY   │      in Al Wukair               │
│            │   (seal)     │                                 │
│            ╰──────────────╯      Opens 4 April 2027         │
│                                  ─────────────────          │
│                                  [Register interest]        │
├─────────────────────────────────────────────────────────────┤
│ ▌ Why us              ← green key                           │
├─────────────────────────────────────────────────────────────┤
│ ▌ The campus          ← gold key                            │
│   Construction status, dated. Render drops in when it exists│
├─────────────────────────────────────────────────────────────┤
│ ▌ Curriculum          ← indigo key                          │
├─────────────────────────────────────────────────────────────┤
│ ▌ The DPS group       ← purple key                          │
│   Two sister schools already operating. Credibility anchor. │
├─────────────────────────────────────────────────────────────┤
│  INK GROUND — full seal, gold on dark                       │
│  Address · Sister schools · Contact                         │
└─────────────────────────────────────────────────────────────┘
```

### 5.1 The quadrant structure rule

Quadrant colours key the four content sections. **Motifs are not mapped to content
pillars.**

This was a deliberate cut. The first version of this plan mapped sail→campus,
globe→curriculum, stars→students, and waves→nothing — the waves mapping was invented to
fill a slot. That is exactly the decorative imposition the brief warns against. Colour
keying is legitimate because the content genuinely divides four ways; claiming iconographic
meaning that isn't there is not.

### 5.2 Known risk

"The campus" has no imagery until real architectural renders exist. If that section runs
thin, the page reads unfinished in the bad way rather than the honest way. It must be
carried by specific, dated construction copy. The section is built so a render drops
straight in.

---

## 6. Quality floor — non-negotiable

- WCAG 2.2 AA. Semantic HTML, correct heading order, visible keyboard focus, real labels.
- **Never** `user-scalable=no` or `maximum-scale=1`. (DPS-MIS does this; it blocks
  pinch-zoom and is an accessibility failure.)
- Responsive from 320px. Test at 360px.
- **No hotlinked third-party assets.** Everything self-hosted. (Monarch loads an image from
  its theme vendor's server — supply-chain risk on a school site.)
- No framework or version numbers in markup or headers.
- Performance budget set and held on a slow connection.
- Every string, image path, link, and integration URL from config — never hardcoded in
  components. This site gets handed to non-technical staff and a CMS later.

---

## 7. Motion

- **One** orchestrated moment: the Assembly. Not effects scattered per section.
- Micro-interactions only on things that respond to input: hover, focus, form state.
- No scroll-jacking. No long entrance delays. Nothing blocks reading.
- `prefers-reduced-motion` fully respected — a genuinely complete experience with motion off.
- `transform` and `opacity` only. 60fps on mid-range Android, verified under CPU throttling.
- No WebGL or video backgrounds. A looping hero video costs several MB and fails the
  performance floor for the target device and connection.

### 7.1 Imagery policy

**No AI-generated representational imagery of the campus.** Generating a photorealistic
school building for a school that is currently a construction site shows a parent a building
that does not exist and will not look like that — for a family making a multi-year enrolment
decision, that functions as a claim. The brief already refuses stock photography of another
school's campus; a synthetic render is worse.

AI-generated **non-representational** work (texture, pattern, abstraction derived from the
shield motifs) is acceptable — that is decoration, not a claim.

Real architectural renders drop into "The campus" when they exist.

---

## 8. Out of scope — do not build

- Any parent, student, or staff login
- Student records, grades, attendance, fee payment, document upload
- A CMS backend
- Any page other than the homepage

Leave named, config-driven placeholders for `/portal`, `/apply`, `/pay` that **hide
themselves when unconfigured.** The school's SIS fills these later.

---

## 9. Open questions

1. Official meaning of the top-left gold quadrant (sail / architectural form).
2. Registration-interest **and newsletter** destination — where do they submit, given no
   backend? Options: `mailto:`, a third-party form endpoint, or a disabled state until
   hosting is decided. Both need the same answer.
3. Confirmed P.O. Box, street address, phone, and email for the Al Wukair campus.
4. Whether construction progress will be supplied on a recurring basis (this determines
   whether "The campus" can be a reason to return before 2027).
5. Which quick links actually exist at launch, and their URLs. Anything unconfirmed ships
   hidden rather than dead.
6. Grades/classes offered at opening, and whether all years open at once or phase in.

## 10. Materials requested from the client

Ordered by how much they unblock.

1. **Logo as vector (AI / EPS / SVG).** Blocking. The current asset is a 933KB raster
   knocked out from a JPEG; the Assembly needs real paths to draw, and the shield mark
   (§4) is currently my own interpretation awaiting sign-off.
2. **Construction photographs.** Highest-value imagery on the site. Phone photos of the
   actual site are worth more than renders — they are evidence, and they are exactly the
   honesty §7.1 requires.
3. Site plan / masterplan drawings.
4. Architectural renders, when they exist.
5. Confirmed facts per §9.
