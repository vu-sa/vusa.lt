---
title: "mano.vusa.lt v1.0: Platform Modernization"
date: 2026-02-07
author: Justinas Kavoliūnas
featured: true
tags:
  - update
excerpt: The biggest mano.vusa.lt update — new representation management system, public meetings, guided tours, content updates and much more.
---

<div class="blog-post-header">
  <div class="blog-post-tags">
    <span class="blog-post-tag">update</span>
    <span class="blog-post-tag">v1.0</span>
  </div>
  <h1 class="blog-post-title">mano.vusa.lt v1.0: Platform Modernization</h1>
  <div class="blog-post-meta">
    <span class="blog-post-author">
      <span class="author-initials">JK</span>
      Justinas Kavoliūnas
    </span>
    <span class="blog-post-date">February 7, 2026</span>
  </div>
</div>

We're excited to present the biggest mano.vusa.lt platform update. This version includes a new representation management system, public meetings, guided tours, and many other improvements.

## 🧭 Representation Management

Fully revamped representation dashboard (`/dashboard/atstovavimas`):

- **Gantt timeline** — interactive institution meeting timeline with holiday periods, periodicity and configurable settings (saved in browser)
- **Representative activity** — administrators can see student representative activity
- **Check-in system** — ability to indicate no upcoming meeting for a period (up to 3 months ahead), with notes
- **Meeting creation** — updated meeting creation modal with agenda item import from previous meetings
- **Agenda item management** — reorder agenda items and indicate whether the item was raised by students

## 🌐 Public Meetings

For transparency, some meetings are now shown publicly:

- Institution pages show meetings grouped by academic year
- Dedicated public meeting page with agenda, votes and decisions
- Search all public meetings with Typesense
- Admin settings for which institutions and meetings are publicly visible

### 🎓 Guided Tours

New interactive tour system helps users learn the platform:

- Automatic tours on first page visit
- Help icon in the top bar shows available tours
- Tour progress is saved — no need to repeat completed tours
- Option to reset tours in settings

### 👥 User Update Wizard

New wizard to easily update duty users — select institution, then duty, and add new or remove existing users.

### 📰 News and Content Updates

- **News layouts** — 4 different layout options for news
- **Page layouts** — 3 options for content pages
- **Highlights** — up to 3 highlighted elements in news
- **Social embeds** — Facebook and Instagram content embedding
- **Related news** — 3 related articles shown after each news item

### 📅 Calendar Timeline

Replaced the old calendar component with a new event timeline — clearer and simpler to use.

### 📇 Contact Page

Updated contact page uses Typesense search — fast, with filters and discoverable through unified search.

### 🔧 Other Improvements

- ✨ **Updated sidebar** — animations, clearer quick actions, better active page indicator
- 🔄 **View transitions** — smooth page transitions across the platform
- 📱 **PWA and notifications** — platform works as a mobile app, updated notifications and task creation for representatives
- 🔍 **Agenda item search** — administrators can search agenda items
- 🔤 **New font** — Atkinson Hyperlegible Next for better readability
- 📋 **Registration form** — student representation registration form
- 🏨 **Updated reservation views** — improved reservation tables
- 🔐 **Microsoft authentication** — proper error message on failed login
- ✏️ **Content editor** — updated content editor interface
- 🔗 **Institution relationships** — extended relationship system with directional and bidirectional relationships

---

*This update includes 1384 changed files and 195 commits. Thank you to everyone who contributed!* 🚀

<style>
.blog-post-header {
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--vp-c-divider);
}

.blog-post-header .blog-post-title {
  font-size: 32px !important;
  font-weight: 800 !important;
  line-height: 1.2 !important;
  margin: 8px 0 16px !important;
  padding: 0 !important;
  border: none !important;
}

.blog-post-tags {
  display: flex;
  gap: 6px;
}

.blog-post-tag {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--vp-c-brand-1);
  background: var(--vp-c-brand-soft);
  padding: 2px 10px;
  border-radius: 9999px;
}

.blog-post-meta {
  display: flex;
  align-items: center;
  gap: 16px;
  color: var(--vp-c-text-2);
  font-size: 14px;
}

.blog-post-author {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}

.author-initials {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, oklch(0.5 0.12 25), oklch(0.75 0.12 65));
  color: white;
  font-size: 11px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.blog-post-date {
  color: var(--vp-c-text-3);
}
</style>
