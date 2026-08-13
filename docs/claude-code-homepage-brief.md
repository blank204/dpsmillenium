# Claude Code — DPS Millennium International School, homepage build

## Phase 0 — tooling discovery (do this first, time-box it to ~15 minutes)

Before writing any code, audit what's available to you:

1. Check your built-in skills. `/mnt/skills/public/frontend-design/SKILL.md` is directly relevant to this brief — read it and follow its two-pass process (plan → critique the plan → build → critique the build). Read any other SKILL.md that plausibly applies.
2. Search the plugin marketplace for things in these categories, and install what's clearly useful:
   - accessibility auditing (axe, WCAG checking)
   - Lighthouse / performance budgets
   - Next.js and React tooling
   - browser automation for screenshots, so you can look at your own work and critique it
   - image optimisation
3. Report back what you installed and what you deliberately skipped, with one line of reasoning each.

Don't rabbit-hole. If a plugin isn't obviously earning its place, skip it and move on. The build matters more than the toolkit.

---

## The project

We are building the public website for **DPS Millennium International School**, a new CBSE school in Al Wukair, Qatar, part of the DPS Group of Institutions. It has two sister schools already operating in Doha (DPS Modern Indian School, DPS Monarch International School).

**Opening: 4 April 2027.** Construction is underway. There is no campus to photograph, no staff to photograph, no students. Currently the only thing at dpsmillennium.com is a GoDaddy Website Builder holding page with a Getty stock photo on it.

This build is a **prototype homepage**, not the production site. Hosting is still undecided by the school, so build it as a **static Next.js export** with no backend dependencies — it needs to be able to run anywhere. Its job is partly to be good, and partly to win an internal argument about not building this on a website builder.

**Target audience:** parents in Qatar, mostly Indian expat families, deciding where to enrol a child for April 2027. Most of them will see this on a mid-range Android phone. Secondary audience: the school's own board and the Ministry.

**The single job of this homepage:** make a parent believe this school is real, serious, and worth registering interest in, eighteen months before it opens.

---

## Brand facts

The logo is a circular bilingual seal (English and Arabic) with a four-quadrant shield at its centre. Colours below are sampled directly from the supplied artwork.

| Role | Hex | Contrast on white | Use |
|---|---|---|---|
| Purple (primary) | `#400068` | 14.8:1 | Body text, headings, primary UI. Excellent contrast. |
| Green (secondary) | `#206838` | 6.8:1 | Secondary text, accents, safe for body copy. |
| Gold (accent) | `#D8A800` | 2.2:1 | **Never for text on white.** Fills, rules, highlights, or text on dark only (8.2:1 on ink). |
| Red | `#D82818` | 5.0:1 | Use sparingly. Reads as alert/error — don't make it a brand workhorse. |
| Indigo | `#382898` | 10.9:1 | Rare. Deep accents only. |

Six colours is too many to use at equal weight — that's how you get a circus. Establish a clear hierarchy: purple carries the page, green supports, gold punctuates, red and indigo are almost never seen. Pick a background that isn't plain white and isn't the cream-paper default.

**Shield quadrants:** open book (gold), globe with atomic orbits (red), waves (indigo), stars (purple). These are the school's own visual vocabulary — mine them for iconography and structure rather than importing a generic icon set.

**Tagline:** "Empowering Educational Excellence"

**Logo constraint you must solve:** the full seal has fine detail, a double outer ring, and text in two scripts. It will turn to mush below about 120px. Design a lockup system — full seal for the hero and footer, and a simplified shield-only mark for the nav, favicon, and mobile. Don't just scale the seal down and hope.

**Bilingual:** the logo itself is English + Arabic. Full Arabic localisation is Phase 2, but choose a typeface pairing now that has a genuine Arabic companion, and don't hardcode strings or assume left-to-right layout in the CSS. Building RTL-capable from the start costs almost nothing; retrofitting costs weeks.

---

## What "not AI slop" means here

The client has explicitly said they don't want a generic school website and don't want anything that reads as AI-generated. Both are fair, and both are easy to fail.

**Do not build the default school website.** Its symptoms: a hero photo of children laughing in a corridor under a gradient overlay; a headline like "Nurturing Tomorrow's Leaders"; three feature cards with outline icons; a stats counter that ticks up on scroll; a testimonial carousel; every section a centred heading with three columns beneath it.

**Do not build the default AI website either.** Its symptoms: cream background with a high-contrast serif and a terracotta accent; near-black with one acid accent colour; glassmorphism; purple-blue mesh gradients; bento grids used where a list would do; every element fading up on scroll with the same easing; Inter or Poppins as the entire type system.

**Where the distinctiveness should come from:** the school's own situation. It doesn't exist yet. That's not a problem to hide with stock photography — it's the most interesting fact about it, and no other school website in Qatar can say it. A building going up, a date approaching, an institution assembling itself in public. Design for that, honestly.

Ideas worth considering (not a checklist, pick or reject with reasoning):
- The four shield quadrants as a real structural device — but only if the content genuinely divides four ways. Don't impose it decoratively.
- The opening date as a live element rather than a line of text.
- Construction progress as recurring content, so there's a reason to come back before 2027.
- Architectural renders as the hero once we have them. Design the hero so a render can drop in; use something honest in the meantime, not a stock photo of someone else's school.

**Spend your boldness in one place.** One signature moment that people remember, everything around it quiet and disciplined.

**Copy matters as much as layout.** Write real copy, in plain language, from the parent's side of the screen. No "unlock potential," no "holistic journey," no three-item rhetorical lists. If a sentence could appear on any school's website, cut it.

---

## Motion brief

The school wants animation. Give them motion with a job, not decoration:

- One orchestrated moment (a page-load sequence or a single scroll-driven reveal) rather than effects scattered across every section.
- Micro-interactions on things that respond to input: hover, focus, form state.
- Nothing that blocks reading. No scroll-jacking, no content that requires JavaScript to become visible, no long entrance delays.
- `prefers-reduced-motion` fully respected — not just reduced, but a genuinely complete experience with motion off.
- Target 60fps on a mid-range Android. Animate transform and opacity. Test with CPU throttling.
- If a WebGL or 3D element is used, it must be progressive: static image first, enhanced only where the device can handle it, and never a blocker for content or navigation.

---

## Quality floor (non-negotiable)

The sister sites fail several of these; we don't get to.

- WCAG 2.2 AA. Real semantic HTML, correct heading order, visible keyboard focus, proper labels.
- **Never** set `user-scalable=no` or `maximum-scale=1` — DPS-MIS does this and it blocks pinch-zoom. It's an accessibility failure.
- Responsive from 320px up. Test at 360px, that's the realistic phone width here.
- No hotlinked third-party assets. Everything self-hosted. Monarch currently loads an image from its theme vendor's server; that's a supply-chain risk on a school site.
- No framework or version numbers leaked in the markup or headers.
- Fast on a slow connection. Set a performance budget and hold to it.
- Every string, image path, link, and integration URL comes from config, not hardcoded in components — this site will be handed to non-technical staff and a CMS later.

---

## Process

Follow the frontend-design skill's process properly:

1. **Plan first.** A compact token system: 4–6 named colours from the palette above, a deliberate type pairing for display / body / utility roles with Arabic support, a layout concept with ASCII wireframes, and one named signature element.
2. **Critique the plan before building.** Ask honestly whether you'd have produced this same plan for any other school. Where the answer is yes, change it and say what you changed and why.
3. **Build** the homepage only, following the revised plan exactly.
4. **Critique the build.** Screenshot it, look at it, fix what's wrong. Check it at 360px. Check it with motion disabled. Check it with keyboard only.

Show me the plan before you build.

---

## Out of scope — do not build

- Any parent, student, or staff login
- Student records, grades, attendance, fee payment, document upload
- A CMS backend (that comes later, once hosting is decided)
- Any page other than the homepage

Leave named, config-driven placeholders for `/portal`, `/apply`, `/pay` that hide themselves when unconfigured. The school's SIS will fill those in later.
