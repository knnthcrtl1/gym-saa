# Gym SaaS

Multi-tenant gym management SaaS built with Nuxt + Laravel.

## Apps

- `apps/api` - Laravel API
- `apps/web` - Nuxt frontend

## MVP Modules

- Tenant / gym management
- Branch management
- Member management
- Membership plans
- Subscriptions
- Payments
- Check-ins
- Reports

## Tech Stack

- Nuxt 4
- Laravel 13
- Sanctum
- SQLite
- Vuetify

## Local Development

- `apps/api` uses SQLite by default for local development.
- The database file is `apps/api/database/database.sqlite`.

## Development Standards

- See `docs/engineering-standards.md` for backend and frontend implementation rules.

Reading docs. Because they shape how you think. Skimming MDN documentation might save time, yet skipping it entirely trades understanding for speed. Questions deliver replies fast, true. But answers lack structure without context built from reading. Grasping systems beats grabbing outputs every single time.

Debugging from first principles. Start at zero every time. When the stack trace shows up, when reading gets skipped, pasting takes over. A dopamine rush in from the instant results kicks in. Fixing happens through the AI, not me. Understanding the real breakdown never quite lands.

Read every line the AI generates. Start at the beginning, go line by line. Skip nothing. When something feels off, pause. A strange name? Search it. Google is your friend here. An odd structure appears? Stop. Do you understand what the code is actually doing? See each result like code handed down from someone skilled, yet cautious. Believe parts of it, question the rest.

Build something from scratch every month. Without help from Copilot. Not using Claude. Autocomplete limited to basic editor support. (I turned off my AI autocomplete ever since i switched from VScode to Zed) Choose a tiny project, maybe a command line app. Or a little desktop tool with Tauri. Perhaps a quick site made with SvelteKit over two days. Notice how hard it gets sometimes. The struggle shows up. Good. That’s where it matters.

Own your architecture decisions. Decisions about structure belong to you. Letting AI choose your tools (database, framework, destination) can haunt later days. For small coding tasks, assistance makes sense. Big moves shape long-term paths. Write down why you picked what you did. Ownership means standing by choices, clearly explained.

Rubber-duck the AI’s code. Start by talking through the AI’s output like you’re telling a friend. Say it aloud or type it into a note before saving any changes (I talk about it to myself in the shower). When words won’t come, that means clarity hasn’t arrived. Hold off on saving until understanding lands. If meaning stays foggy, the work isn’t ready.

Warning signs you’re already cooked
If one of these fits how you feel right now, maybe today is the day to start fresh instead:

You can’t remember the last time you opened official documentation
Your first instinct when seeing an error is to paste it into a chat, not read it
You’ve shipped features where you’d struggle to explain the logic to others
You feel genuinely anxious coding without AI assistance
You’ve stopped noticing when the AI suggests something subtly wrong

The honest take
In our industry at the very least, AI is not vanishing any time soon, and if you completely ignore AI you’ll be left behind fast. You should know that what makes us matter as engineers isn’t typing prompts; it’s the gut sense for flaws, choices under pressure, out-of-the-box thinking, and most important of all: our Humanity Years may pass while i master my skills slowly. Should I quit now, those habits will fade away.
