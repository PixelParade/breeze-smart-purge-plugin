#!/usr/bin/env python3
"""Rebuild plugin icons: transparent canvas, artwork scaled to cover the square."""

from __future__ import annotations

from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / "assets"
WPORG = ASSETS / "wporg"
SIZES = (128, 256, 512)

# Legacy baked-tile colors (stripped when extracting artwork).
LEGACY_TILE_COLORS = {
	(0xF0, 0xF0, 0xF1),
	(0xE9, 0xE9, 0xEA),
}


def strip_legacy_tile(source: Image.Image) -> Image.Image:
	rgba = source.convert("RGBA")
	px = rgba.load()
	w, h = rgba.size
	for y in range(h):
		for x in range(w):
			r, g, b, a = px[x, y]
			if a and (r, g, b) in LEGACY_TILE_COLORS:
				px[x, y] = (0, 0, 0, 0)
	return rgba


def extract_content(source: Image.Image) -> Image.Image:
	rgba = strip_legacy_tile(source)
	bbox = rgba.split()[3].getbbox()
	if not bbox:
		raise ValueError("Source icon has no opaque pixels after tile strip.")
	return rgba.crop(bbox)


def compose_cover(size: int, content: Image.Image) -> Image.Image:
	canvas = Image.new("RGBA", (size, size), (0, 0, 0, 0))
	w, h = content.size
	scale = max(size / w, size / h)
	new_w = max(1, round(w * scale))
	new_h = max(1, round(h * scale))
	scaled = content.resize((new_w, new_h), Image.Resampling.LANCZOS)
	offset = ((size - new_w) // 2, (size - new_h) // 2)
	canvas.paste(scaled, offset, scaled)
	return canvas


def main() -> None:
	source_path = ASSETS / "icon-256x256.png"
	if not source_path.exists():
		source_path = WPORG / "icon-256x256.png"
	content = extract_content(Image.open(source_path))

	for size in SIZES:
		icon = compose_cover(size, content)
		out_agency = ASSETS / f"icon-{size}x{size}.png"
		icon.save(out_agency, optimize=True)
		print(f"wrote {out_agency.relative_to(ROOT)}")
		if size in (128, 256):
			out_wporg = WPORG / f"icon-{size}x{size}.png"
			icon.save(out_wporg, optimize=True)
			print(f"wrote {out_wporg.relative_to(ROOT)}")


if __name__ == "__main__":
	main()
