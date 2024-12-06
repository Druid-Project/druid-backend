# Druid.fi Website Renewal Project - Report

## Project Overview

The goal of the project is to renew the Druid.fi website to better serve customers by making it modern, personalized, and integrated with a headless Drupal backend, a React-based frontend, and an open-source marketing automation platform (Mautic).

## Project Requirements

1. **Headless Drupal Backend**:

   - The website should have a headless Drupal-powered backend to manage content.

2. **Modern React-based Frontend**:

   - The frontend should be built using React, connected to the Drupal backend via JSON-API.

3. **Mautic Integration**:

   - Integrate Mautic to enable personalization and dynamic content display based on user segments.

4. **Dynamic Content**:

   - Use Mautic’s dynamic content and segments to personalize content across the site.

5. **Content Control via Segments**:

   - Ensure editors can control content visibility based on Mautic segments without writing HTML.

6. **Dynamic Paragraphs**:
   - Implement dynamic paragraphs in Drupal that allow content editors to compose pages with flexible blocks.

---

## Work Done So Far

### Backend (Drupal)

1. **Paragraph Types Created**:

   - Hero, Our Services Section, Card, Feature Section, Blog, Blog Cards.

2. **Content Types Created**:

   - Created content types: Home, Services, Contact, Career, About, Blog.
   - Linked content types to corresponding paragraph types.

3. **Content Created**:

   - Created content for each content type (e.g., Homepage, Services pages, Blogs).

4. **Mautic Integration**:
   - Set up Mautic with Lando and connected it to Drupal using the Mautic Paragraphs module.
   - Initialized Mautic segments: `visitors` (who visit the frontend) and `users` (who submit Mautic forms).
   - Verified the data flow from Mautic to Drupal.

### Frontend (React)

1. **React Frontend Built**:

   - Built a frontend with React, Vite, Redux Toolkit, and Material-UI (MUI).
   - Integrated with the Drupal backend via JSON-API.

2. **Content Fetching**:

   - Fetched content from Drupal backend to the frontend using Redux (with a single content slice).

3. **Mautic Integration**:
   - Integrated Mautic for tracking (mtc_id, mautic_device_id).
   - Captured the segments from Mautic and passed them to the React components for personalized content display.

---

## Remaining Tasks

### 1. **Personalization with Mautic Segments**

- **Status**: In Progress
- **Tasks Remaining**:
  - Test the dynamic content features in Mautic to personalize content.
  - Implement conditional content rendering in the frontend based on the Mautic segments.
  - Configure **Mautic Paragraphs** module for dynamic content visibility based on segments.

### 2. **Build Content Visibility Based on Mautic Segments**

- **Status**: In Progress
- **Tasks Remaining**:
  - Develop functionality to show/hide content in Drupal based on Mautic segments.
  - Ensure content editors can control visibility through a user-friendly interface.
  - Use Mautic segment data to conditionally render paragraphs in Drupal.

### 3. **Dynamic Paragraphs for Flexible Page Layouts**

- **Status**: Not Started
- **Tasks Remaining**:
  - Implement dynamic paragraphs in Drupal, allowing users to compose flexible page layouts using blocks with various content types.
  - Ensure the system supports various layouts, such as service cards, blog posts, and feature sections.
  - Make it easy for editors to drag and drop blocks without needing to write HTML.

### 4. **Final Testing and Quality Assurance**

- **Status**: Not Started
- **Tasks Remaining**:
  - Test the integration of Mautic segments in both frontend and backend.
  - Ensure dynamic content is rendered correctly based on user segments.
  - Perform end-to-end testing to verify content visibility and personalization.
  - Ensure the website is fully responsive and accessible.

---

## Conclusion

The project has made good progress with the backend and frontend setups, including Mautic integration and segment creation. The next steps involve testing and refining the personalization features and building dynamic paragraphs to allow content editors to create flexible page layouts. Once these remaining tasks are completed, the website will be ready for final testing and deployment.

---

## Project Status Summary

| Task                                              | Status      | Remaining Work                           |
| ------------------------------------------------- | ----------- | ---------------------------------------- |
| **Set-up a working Drupal installation**          | ✅ Done     | None                                     |
| **Build a frontend with React**                   | ✅ Done     | None                                     |
| **Set-up a working Mautic instance**              | ✅ Done     | None                                     |
| **Test personalization features (Mautic)**        | In Progress | Test dynamic content, Mautic Paragraphs  |
| **Show content based on Mautic segments**         | In Progress | Implement segment-based visibility logic |
| **Build dynamic paragraphs for flexible layouts** | Not Started | Implement dynamic paragraphs             |
| **Final Testing and QA**                          | Not Started | End-to-end testing                       |
