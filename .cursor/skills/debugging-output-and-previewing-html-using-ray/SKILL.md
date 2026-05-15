---
name: debugging-output-and-previewing-html-using-ray
description: "Sends variables, debug output, HTML previews, tables, and timing data to the Ray desktop application via its local HTTP API. Use when user says 'send to Ray,' 'show in Ray,' 'debug in Ray,' 'log to Ray,' 'display in Ray,' or wants to visualize data, preview HTML designs, display debug output, or render diagrams in Ray. Constructs JSON payloads for Ray payload types (log, custom, table, color, label, measure, notify) and sends them via HTTP POST to Ray's local server."
metadata:
  author: Spatie
  tags:
    - debugging
    - logging
    - visualization
    - ray
---

# Ray Skill

## Overview

Ray is Spatie's desktop debugging application for developers. Send data directly to Ray by making HTTP requests to its local server.

This can be useful for debugging applications, or to preview design, logos, or other visual content.

This is what the `ray()` PHP function does under the hood.

## Workflow

1. **Check availability**: `GET http://localhost:23517/_availability_check` — Ray responds with HTTP 404 when running (endpoint doesn't exist, but the server is up). A connection error means Ray is not running.
2. **Send payload**: `POST http://localhost:23517/` with JSON body containing `uuid`, `payloads` array, and optional `meta`.
3. **Apply modifiers**: Send `color`, `label`, and `size` payloads in the same request (same `uuid`) to style a log entry.

## Connection Details

| Setting | Default | Environment Variable |
|---------|---------|---------------------|
| Host | `localhost` | `RAY_HOST` |
| Port | `23517` | `RAY_PORT` |
| URL | `http://localhost:23517/` | - |

## Request Format

**Method:** POST
**Content-Type:** `application/json`
**User-Agent:** `Ray 1.0`

### Basic Request Structure

```json
{
  "uuid": "unique-identifier-for-this-ray-instance",
  "payloads": [
    {
      "type": "log",
      "content": { },
      "origin": {
        "file": "/path/to/file.php",
        "line_number": 42,
        "hostname": "my-machine"
      }
    }
  ],
  "meta": {
    "ray_package_version": "1.0.0"
  }
}
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `uuid` | string | Unique identifier for this Ray instance. Reuse the same UUID to update an existing entry. |
| `payloads` | array | Array of payload objects to send |
| `meta` | object | Optional metadata (ray_package_version, project_name, php_version) |

### Origin Object

Every payload requires an `origin` with `file`, `line_number`, and `hostname`. Shown once below — include it in every payload in actual requests:

```json
{
  "file": "/Users/dev/project/app/Controller.php",
  "line_number": 42,
  "hostname": "dev-machine"
}
```

## Payload Types

### Log (Send Values)

```json
{
  "type": "log",
  "content": {
    "values": ["Hello World", 42, {"key": "value"}]
  }
}
```

### Custom (HTML/Text Content)

```json
{
  "type": "custom",
  "content": {
    "content": "<h1>HTML Content</h1><p>With formatting</p>",
    "label": "My Label"
  }
}
```

### Table

```json
{
  "type": "table",
  "content": {
    "values": {"name": "John", "email": "john@example.com", "age": 30},
    "label": "User Data"
  }
}
```

### Modifiers (Color, Label, Size)

Modifier payloads style a preceding log entry. Send them in the same request with the same `uuid`:

| Type | Content | Values |
|------|---------|--------|
| `color` | `{ "color": "<value>" }` | `green`, `orange`, `red`, `purple`, `blue`, `gray` |
| `screen_color` | `{ "color": "<value>" }` | Same colors (sets screen background) |
| `label` | `{ "label": "<text>" }` | Any string |
| `size` | `{ "size": "<value>" }` | `sm`, `lg` |

### Notify (Desktop Notification)

```json
{
  "type": "notify",
  "content": {
    "value": "Task completed!"
  }
}
```

### New Screen

```json
{
  "type": "new_screen",
  "content": {
    "name": "Debug Session"
  }
}
```

### Measure (Timing)

```json
{
  "type": "measure",
  "content": {
    "name": "my-timer",
    "is_new_timer": true,
    "total_time": 0,
    "time_since_last_call": 0,
    "max_memory_usage_during_total_time": 0,
    "max_memory_usage_since_last_call": 0
  }
}
```

For subsequent measurements, set `is_new_timer: false` and provide actual timing values.

### Simple Payloads (No Content)

These payloads only need a `type` and empty `content`:

```json
{
  "type": "separator",
  "content": {}
}
```

| Type | Purpose |
|------|---------|
| `separator` | Add visual divider |
| `clear_all` | Clear all entries |
| `hide` | Hide this entry |
| `remove` | Remove this entry |
| `confetti` | Show confetti animation |
| `show_app` | Bring Ray to foreground |
| `hide_app` | Hide Ray window |

## Combining Multiple Payloads

Send multiple payloads in one request. Use the same `uuid` to apply modifiers (color, label, size) to a log entry:

```json
{
  "uuid": "abc-123",
  "payloads": [
    {
      "type": "log",
      "content": { "values": ["Important message"] },
      "origin": { "file": "test.php", "line_number": 1, "hostname": "localhost" }
    },
    {
      "type": "color",
      "content": { "color": "red" },
      "origin": { "file": "test.php", "line_number": 1, "hostname": "localhost" }
    },
    {
      "type": "label",
      "content": { "label": "ERROR" },
      "origin": { "file": "test.php", "line_number": 1, "hostname": "localhost" }
    },
    {
      "type": "size",
      "content": { "size": "lg" },
      "origin": { "file": "test.php", "line_number": 1, "hostname": "localhost" }
    }
  ],
  "meta": {}
}
```

## Example: Complete Request

Send a green, labeled log message:

```bash
curl -X POST http://localhost:23517/ \
  -H "Content-Type: application/json" \
  -H "User-Agent: Ray 1.0" \
  -d '{
    "uuid": "my-unique-id-123",
    "payloads": [
      {
        "type": "log",
        "content": {
          "values": ["User logged in", {"user_id": 42, "name": "John"}]
        },
        "origin": {
          "file": "/app/AuthController.php",
          "line_number": 55,
          "hostname": "dev-server"
        }
      },
      {
        "type": "color",
        "content": { "color": "green" },
        "origin": { "file": "/app/AuthController.php", "line_number": 55, "hostname": "dev-server" }
      },
      {
        "type": "label",
        "content": { "label": "Auth" },
        "origin": { "file": "/app/AuthController.php", "line_number": 55, "hostname": "dev-server" }
      }
    ],
    "meta": {
      "project_name": "my-app"
    }
  }'
```

## Getting Ray Information

### Get Windows

Retrieve information about all open Ray windows:

```
GET http://localhost:23517/windows
```

Returns an array of window objects with their IDs and names:

```json
[
  {"id": 1, "name": "Window 1"},
  {"id": 2, "name": "Debug Session"}
]
```

### Get Theme Colors

Retrieve the current theme colors being used by Ray:

```
GET http://localhost:23517/theme
```

Returns the theme information including color palette. Use these colors when sending custom HTML content to ensure it matches Ray's current theme.

```json
{
  "name": "Dark",
  "colors": {
    "primary": "#000000",
    "secondary": "#1a1a1a",
    "accent": "#3b82f6"
  }
}
```
