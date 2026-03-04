<?php
// Serve a placeholder SVG when no listing image is available
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
?>
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">
  <rect width="400" height="300" fill="#1a1a2e"/>
  <text x="50%" y="42%" dominant-baseline="middle" text-anchor="middle"
        font-family="system-ui,sans-serif" font-size="48" fill="#e91e8c" opacity="0.6">♪</text>
  <text x="50%" y="62%" dominant-baseline="middle" text-anchor="middle"
        font-family="system-ui,sans-serif" font-size="13" fill="#94a3b8">No image available</text>
</svg>
