#!/usr/bin/env python3
"""Rebuild plugin icons with uniform padding and baked checkerboard tile."""

from __future__ import annotations

import os
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / "assets"
WPORG = ASSETS / "wporg"

# Match assets/admin/plugin-assets.css tile colors.
COLOR_A = (0xF0, 0xF0, 0xF1, 255)
COLOR_B = (0xE9, 0xE9, 0xEA, 255)
CONTENT_SCALE = 0.76  # max fraction of canvas used by artwork
SIZES = (128, 256, 512)


def checkerboard(size: int, tile: int) -> Image.Image:
	canvas = Image.new("RGBA", (size, size), COLOR_A)
	px = canvas.load()
	for y in range(size):
		for x in range(size):
			if ((x // tile) + (y // tile)) % 2:
				px[x, y] = COLOR_B
	return canvas


def extract_content(source: Image.Image) -> Image.Image:
	rgba = source.convert("RGBA")
	bbox = rgba.split()[3].getbbox()
	if not bbox:
		raise ValueError("Source icon has no opaque pixels.")
	return rgba.crop(bbox)


def compose(size: int, content: Image.Image) -> Image.Image:
	tile = max(1, round(12 * size / 512))
	canvas = checkerboard(size, tile)
	max_side = max(1, round(size * CONTENT_SCALE))
	w, h = content.size
	scale = min(max_side / w, max_side / h)
	new_w = max(1, round(w * scale))
	new_h = max(1, round(h * scale))
	scaled = content.resize((new_w, new_h), Image.Resampling.LANCZOS)
	offset = ((size - new_w) // 2, (size - new_h) // 2)
	canvas.paste(scaled, offset, scaled)
	return canvas


def main() -> None:
	source_path = WPORG / "icon-256x256.png"
	if not source_path.exists():
		source_path = ASSETS / "icon-256x256.png"
	content = extract_content(Image.open(source_path))

	for size in SIZES:
		icon = compose(size, content)
		out_agency = ASSETS / f"icon-{size}x{size}.png"
		icon.save(out_agency, optimize=True)
		print(f"wrote {out_agency.relative_to(ROOT)}")
		if size in (128, 256):
			out_wporg = WPORG / f"icon-{size}x{size}.png"
			icon.save(out_wporg, optimize=True)
			print(f"wrote {out_wporg.relative_to(ROOT)}")


if __name__ == "__main__":
	main()
