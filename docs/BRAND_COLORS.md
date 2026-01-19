# Erhevo Brand Colors

Official brand color palette configured in `tailwind.config.js`.

## Primary Palette - Deep Ocean

These colors represent depth, calm, and trustworthiness.

### Navy (Primary brand color)
| Shade | Hex | Usage |
|-------|-----|-------|
| 50 | `#E8EDF5` | Light backgrounds, borders |
| 100 | `#C5D1E5` | Hover states, secondary backgrounds |
| 200 | `#9FB3D0` | Disabled states |
| 300 | `#7995BB` | Secondary text |
| 400 | `#5377A6` | Icons |
| 500 | `#2D5991` | Links |
| **600 (DEFAULT)** | `#1A365D` | **Primary headings, logo, important text** |
| 700 | `#142A4A` | Dark accents |
| 800 | `#0E1E37` | Footer backgrounds |
| 900 | `#081224` | Darkest elements |

### Teal (Secondary brand color)
| Shade | Hex | Usage |
|-------|-----|-------|
| 50 | `#E6F4F5` | Very light backgrounds |
| 100 | `#B3DDDF` | Light tint |
| 200 | `#80C6C9` | Light accents |
| 300 | `#4DAFB3` | Secondary buttons |
| 400 | `#1A989D` | Hover states |
| **500 (DEFAULT)** | `#136F74` | **Inactive nav links, secondary text** |
| 600 | `#0F595D` | Dark teal |
| 700 | `#0B4346` | Darker accents |
| 800 | `#072D2F` | Very dark |
| 900 | `#031718` | Near black |

### Aqua (Accent/Highlight)
| Shade | Hex | Usage |
|-------|-----|-------|
| 50 | `#E9F7FA` | Light backgrounds |
| 100 | `#C7ECF2` | Hover backgrounds |
| 200 | `#A5E1EA` | Borders |
| 300 | `#83D6E2` | Light accents |
| 400 | `#5ECBD9` | Secondary accents |
| **500 (DEFAULT)** | `#39B8CF` | **Gradient endpoints, highlights** |
| 600 | `#2E93A6` | Darker aqua |
| 700 | `#236E7D` | Dark accents |
| 800 | `#184954` | Very dark |
| 900 | `#0D242B` | Near black |

## Accent Palette - Golden Glow

These colors add warmth and draw attention to important actions.

### Gold (Warm accent)
| Shade | Hex | Usage |
|-------|-----|-------|
| 50 | `#FDF9F0` | Very light backgrounds |
| 100 | `#FAEFD5` | Light backgrounds |
| 200 | `#F7E5BA` | Borders |
| 300 | `#F4DB9F` | Light accents |
| 400 | `#F2D084` | Secondary accents |
| **500 (DEFAULT)** | `#F0C56F` | **Logo text, highlights** |
| 600 | `#E5AA3A` | Darker gold |
| 700 | `#C08A1F` | Dark gold |
| 800 | `#8B6417` | Very dark |
| 900 | `#563E0E` | Near black |

### Amber (CTA/Action color)
| Shade | Hex | Usage |
|-------|-----|-------|
| 50 | `#FEF5EC` | Light backgrounds |
| 100 | `#FCE4CC` | Hover backgrounds |
| 200 | `#FAD3AC` | Borders |
| 300 | `#F8C28C` | Light accents |
| 400 | `#F6B16C` | Secondary buttons |
| **500 (DEFAULT)** | `#F4933D` | **Active nav links, primary buttons** |
| 600 | `#E5740E` | Button hover states |
| 700 | `#B35A0B` | Dark accent |
| 800 | `#814108` | Very dark |
| 900 | `#4F2805` | Near black |

### Ivory (Warm backgrounds)
| Shade | Hex | Usage |
|-------|-----|-------|
| 50 | `#FFFCF7` | Lightest |
| 100 | `#FFF9EE` | Very light |
| **200 (DEFAULT)** | `#FFF4DD` | **Warm card backgrounds** |
| 300 | `#FFECC4` | Accents |
| 400 | `#FFE4AB` | Darker accents |
| 500 | `#FFDC92` | Gold-tinted |

## Neutral

### Soft White
- **DEFAULT**: `#FAFAFA` - Main page background

## Usage Guidelines

### Text Colors
- **Headings**: `text-navy` (DEFAULT #1A365D)
- **Body text**: `text-gray-700` or `text-teal`
- **Links**: `text-teal hover:text-navy`
- **Active states**: `text-amber`

### Backgrounds
- **Page background**: `bg-[#FAFAFA]` or `bg-soft`
- **Cards**: `bg-white`
- **Warm cards**: `bg-ivory`
- **Navigation**: `bg-white`
- **Footer**: `bg-navy-800`

### Borders
- **Default**: `border-navy-50`
- **Accent**: `border-aqua`
- **Active**: `border-amber`

### Buttons
- **Primary (CTA)**: `bg-amber hover:bg-amber-600 text-white`
- **Secondary**: `bg-teal hover:bg-teal-600 text-white`
- **Outline**: `border-navy text-navy hover:bg-navy-50`

### Gradients
- **Hero/Feature**: `bg-gradient-to-r from-teal to-aqua`
- **Warm accent**: `bg-gradient-to-r from-amber to-gold`

## Tailwind Classes Quick Reference

```html
<!-- Headings -->
<h1 class="text-navy">Main Heading</h1>
<h2 class="text-teal">Subheading</h2>

<!-- Links -->
<a class="text-teal hover:text-navy">Regular Link</a>
<a class="text-amber border-b-2 border-amber">Active Nav Link</a>

<!-- Buttons -->
<button class="bg-amber hover:bg-amber-600 text-white">CTA Button</button>
<button class="bg-teal hover:bg-teal-600 text-white">Secondary</button>

<!-- Cards -->
<div class="bg-white border border-navy-50">Card</div>
<div class="bg-ivory">Warm Card</div>

<!-- Gradient Background -->
<div class="bg-gradient-to-r from-teal to-aqua">Gradient Section</div>
```

## Brand Assets Location

Logo files are stored in `/public/images/`:
- `erhevo-logo.png` - Main logo
- Other logo variations

Color reference: `/public/images/erhevo_colors.md`
