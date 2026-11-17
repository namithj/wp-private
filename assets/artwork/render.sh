#!/usr/bin/env bash
#
# Render the WordPress.org listing artwork into .wordpress-org/ with ImageMagick.
# icon.svg is the vector source WordPress.org shows in browsers; the PNGs are drawn here with ImageMagick's
# -draw (MVG), because its SVG renderer drops strokes. Shapes are drawn at 4x (icon) / 2x (banner) and scaled down.
#
#   bash assets/artwork/render.sh
#
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
OUT=.wordpress-org
mkdir -p "${OUT}"

INDIGO='#1e1b4b'
INDIGO_2='#312e81'
CARD='#f8fafc'
LINE='#a5b4fc'
HIDDEN='#e2e8f0'
AMBER='#f59e0b'
TAG='#c7d2fe'

# --- Icon: 256 units, drawn at 4x -------------------------------------------------------------
magick -size 1024x1024 xc:none -draw "
	affine 4,0,0,4,0,0
	fill ${INDIGO} roundrectangle 0,0 255,255 48,48
	fill ${CARD} roundrectangle 56,40 200,216 14,14
	fill ${INDIGO} roundrectangle 76,64 180,76 6,6
	fill ${LINE} roundrectangle 76,88 164,98 5,5
	fill ${LINE} roundrectangle 76,106 136,116 5,5
	fill ${HIDDEN} roundrectangle 76,140 180,150 5,5
	fill ${HIDDEN} roundrectangle 76,158 156,168 5,5
	fill ${HIDDEN} roundrectangle 76,176 172,186 5,5
	fill none stroke ${AMBER} stroke-width 11 stroke-linecap round
	path 'M150 150 V136 A22 22 0 0 1 194 136 V150'
	stroke none fill ${AMBER} roundrectangle 136,146 208,206 12,12
	fill ${INDIGO} circle 172,170 180,170
	fill ${INDIGO} roundrectangle 168,172 176,190 4,4
" -filter Lanczos -resize 256x256 -depth 8 -strip "${OUT}/icon-256x256.png"
magick "${OUT}/icon-256x256.png" -filter Lanczos -resize 128x128 -depth 8 -strip "${OUT}/icon-128x128.png"
cp assets/artwork/icon.svg "${OUT}/icon.svg"

# --- Banner: 1544x500 units, drawn at 2x --------------------------------------------------------
magick -size 3088x1000 "xc:${INDIGO}" -draw "
	affine 2,0,0,2,0,0
	fill ${INDIGO_2} circle 1250,250 1560,250
	fill ${AMBER} roundrectangle 112,118 192,128 5,5
	fill ${CARD} roundrectangle 1010,70 1330,450 24,24
	fill ${INDIGO} roundrectangle 1050,110 1270,132 11,11
	fill ${LINE} roundrectangle 1050,165 1280,179 7,7
	fill ${LINE} roundrectangle 1050,195 1250,209 7,7
	fill ${LINE} roundrectangle 1050,225 1205,239 7,7
	fill ${HIDDEN} roundrectangle 1050,290 1280,304 7,7
	fill ${HIDDEN} roundrectangle 1050,320 1230,334 7,7
	fill ${HIDDEN} roundrectangle 1050,350 1260,364 7,7
	fill ${HIDDEN} roundrectangle 1050,380 1210,394 7,7
	fill none stroke ${AMBER} stroke-width 22 stroke-linecap round
	path 'M1240 300 V262 A50 50 0 0 1 1340 262 V300'
	stroke none fill ${AMBER} roundrectangle 1210,295 1370,430 24,24
	fill ${INDIGO} circle 1290,350 1306,350
	fill ${INDIGO} roundrectangle 1282,352 1298,395 8,8
" \
	-font URW-Gothic-Demi -pointsize 200 -fill white -annotate +220+470 'WP Private' \
	-font NimbusSans-Regular -pointsize 72 -fill "${TAG}" -annotate +226+600 'Gated content for posts & pages' \
	-filter Lanczos -resize 1544x500 -depth 8 -strip "${OUT}/banner-1544x500.png"
magick "${OUT}/banner-1544x500.png" -filter Lanczos -resize 772x250 -depth 8 -strip "${OUT}/banner-772x250.png"

magick identify -format '%f %wx%h %b\n' "${OUT}"/icon-*.png "${OUT}"/banner-*.png
