# WordPress Custom REST Endpoint — Random Movie from TMDB

This document explains how to install, configure, and test a custom WordPress REST API endpoint that returns information about one **random movie** retrieved from the The Movie Database API.

The endpoint is designed to:

* Work inside a **WordPress theme** (not a plugin)  
* Use **WordPress native APIs** wherever possible  
* Require **authenticated access only**  
* Support **stateless authentication via Application Passwords**  
* Cache movie datasets to avoid excessive external API calls

The example installation below assumes:

Theme: Understrap

Site URL: https://nature.marziodev.test/

---

# 1\. Overview

Once installed and configured, the custom endpoint will be available at:

https://nature.marziodev.test/wp-json/marzio/v1/random-movie

The endpoint will:

1. Verify the request is authenticated.  
2. Retrieve movie data from TMDB (cached for performance).  
3. Select one random movie.  
4. Return a JSON response.

Example response:

{  
"title": "Dune",  
"overview": "Paul Atreides joins the Fremen...",  
"release\_date": "2021-10-22",  
"rating": 8.2,  
"poster\_path": "/poster.jpg"  
}

---

# 2\. Requirements

Before installing the endpoint ensure the following are available:

* WordPress installation  
* Administrator access  
* A theme with an `/inc` folder (example: Understrap)  
* A TMDB API key  
* Postman, Insomnia, or cURL for testing

---

# 3\. Obtain a TMDB API Key

Create a free developer account at:  
The Movie Database  
Steps:

1. Register an account  
2. Navigate to **API Settings**  
3. Request an API key  
4. Copy the key

You will need it during configuration.

---

## 4\. Installation

This implementation is designed to run inside the **Understrap Child theme**.

Theme information:  
Theme Name: Understrap Child  
Text Domain: understrap-child  
Using a child theme ensures that the custom functionality is preserved when the parent theme is updated.

---

### Step 1 — Locate the Child Theme Directory

Navigate to the WordPress theme directory:  
/wp-content/themes/understrap-child/

---

### Step 2 — Create the INC Directory (if not present)

Create the folder:  
/wp-content/themes/understrap-child/inc/

---

### Step 3 — Create the Endpoint File

Create the file:  
/wp-content/themes/understrap-child/inc/tmdb-api.php

Paste the PHP implementation of the custom endpoint inside this file.

---

### Step 4 — Load the File from the Child Theme

Open the child theme file:  
/wp-content/themes/understrap-child/functions.php

Add the following line:  
require\_once get\_stylesheet\_directory() . '/inc/tmdb-api.php';

**Important:** `get_stylesheet_directory()` ensures the file is loaded from the **child theme**, not the parent theme.

---

### Step 5 — Verify Theme Activation

Ensure the **Understrap Child** theme is active.

WordPress Admin:

Dashboard  
→ Appearance  
→ Themes

Active theme should be:  
Understrap Child

---

Once the child theme loads the file, the custom REST endpoint will automatically be registered during WordPress initialization.

---

# 5\. Configure the TMDB API Key

After installing the code, configure the API key from the WordPress admin panel.

Navigate to:

Dashboard  
→ Settings  
→ TMDB API

Insert your API key and save.  
The key is stored in the WordPress options table using a namespaced option:

marzio\_tmdb\_api\_key

---

# 6\. Caching Strategy

Movie datasets are cached using the WordPress **Transients API**.  
Cache details:  
Transient name: marzio\_tmdb\_trending\_movies

Duration: 30 minutes  
This prevents excessive requests to the TMDB API and improves performance.

When the cache expires, the next request will automatically fetch a fresh dataset.

---

# 7\. Authentication Model

The endpoint requires **authenticated WordPress users**.

Anonymous requests are rejected.

Authentication uses **Application Passwords**, which enable stateless API access.

Application Passwords are a WordPress feature introduced in WordPress 5.6.

---

# 8\. Create an Application Password

Navigate to:

Users  
→ Profile  
→ Application Passwords

Create a new password:  
Name: API Testing  
WordPress will generate a password similar to:  
abcd EFGH ijkl MNOP qrst UVWX  
Copy it immediately.

You will use this password instead of your normal WordPress password when calling the API.

---

# 9\. Testing the Endpoint with Postman

## Step 1 — Create Request

Method:  
GET

URL:  
https://nature.marziodev.test/wp-json/marzio/v1/random-movie

---

## Step 2 — Configure Authentication

In Postman:  
Authorization  
→ Type: Basic Auth

Enter:  
Username: your\_wordpress\_username  
Password: your\_application\_password

Example:  
Username: marzio  
Password: abcd EFGH ijkl MNOP qrst UVWX

Postman will automatically generate the required Authorization header.

---

## Step 3 — Send Request

Click **Send**.

If authentication succeeds, the API will return a random movie.

---

# 10\. Testing with cURL

The same request can be performed using cURL.

Example command:  
curl \-u username:application\_password \\  
[https://nature.marziodev.test/wp-json/marzio/v1/random-movie](https://nature.marziodev.test/wp-json/marzio/v1/random-movie)

Example:  
curl \-u marzio:abcdEFGHijklMNOPqrstUVWX \\  
https://nature.marziodev.test/wp-json/marzio/v1/random-movie

Successful response:

{  
"title": "Dune",  
"rating": 8.2  
}

---

# 11\. Testing Authentication

A useful test endpoint is the built-in WordPress endpoint:

GET /wp-json/wp/v2/users/me

Example:  
https://nature.marziodev.test/wp-json/wp/v2/users/me

If authentication works, the endpoint will return information about the authenticated user.

---

# 12\. Troubleshooting

## 401 Unauthorized

Possible causes:

* Incorrect username  
* Incorrect Application Password  
* Authorization header missing  
* SSL certificate issues (common in local environments)

If using a self-signed certificate, disable SSL verification in Postman.

---

## TMDB API Key Missing

If the API key is not configured, the endpoint returns:  
tmdb\_missing\_key

Fix:  
Settings → TMDB API → add key

---

## Empty Movie Dataset

If the TMDB API does not return results, the endpoint may respond:

No movie available

Verify:

* API key validity  
* network connectivity  
* TMDB API status

---

# 13\. Security Considerations

The endpoint includes several security practices:

* REST access restricted to authenticated users  
* WordPress Application Password authentication  
* TMDB API key stored using namespaced options  
* caching to limit external requests

This prevents anonymous scraping and reduces the attack surface.

---

# 14\. Architecture Summary

Client (Postman / cURL)

        │  
        │  Basic Auth  
        ▼

WordPress REST API

        │  
        ▼

Custom endpoint

/marzio/v1/random-movie

        │  
        ▼

TMDB API request (cached)

        │  
        ▼

Random movie selection

        │  
        ▼

JSON response

---

# 15\. Notes

This implementation intentionally:

* relies primarily on native WordPress APIs  
* keeps the code lightweight and easy to maintain  
* allows quick integration inside an existing theme

---

End of README.  
