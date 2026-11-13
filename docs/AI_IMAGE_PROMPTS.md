# CustomCart demo images: AI generation prompts

The demo images in this project are **original vector illustrations rendered from code**
(no AI image generator was available when the demo store was built, and none of them
are photos). To switch to AI-generated photography, generate each image below with the
same file name and size and overwrite the file; the database already points at these
names, so nothing else needs to change. Clear the thumbnail cache afterwards
(Sitepanel > account menu > Clear Cache).

**Shared style (add to every prompt):** premium commercial e-commerce photography,
soft professional studio lighting, clean light background (#F1F5F9 to white), subtle
floor shadow, sharp focus, true-to-life fabric texture, no text, no logos, no watermark.

## Products (`uploads/products/`, 1000 x 1000, JPG)

| File | Prompt |
|---|---|
| `premium-cotton-t-shirt-navy.jpg` | Navy blue heavyweight crew-neck cotton T-shirt, front view, laid flat / on invisible mannequin, centred |
| `premium-cotton-t-shirt-white.jpg` | Same T-shirt in off-white |
| `premium-cotton-t-shirt-black.jpg` | Same T-shirt in black |
| `premium-cotton-t-shirt-detail.jpg` | Close-up of the T-shirt's ribbed collar and double-stitched shoulder seam, navy cotton |
| `classic-casual-shirt-sky-blue.jpg` | Sky-blue long-sleeve cotton poplin button-down shirt with chest pocket, front view on invisible mannequin |
| `classic-casual-shirt-white.jpg` | Same shirt in white |
| `classic-casual-shirt-sand.jpg` | Same shirt in sand / light beige |
| `classic-casual-shirt-detail.jpg` | Close-up of the button-down collar and placket buttons |
| `premium-denim-jacket-indigo.jpg` | Indigo trucker denim jacket, copper buttons, orange contrast topstitching, two flap chest pockets, front view |
| `premium-denim-jacket-light-wash.jpg` | Same jacket in light-wash denim |
| `premium-denim-jacket-detail.jpg` | Close-up of the chest pocket flap, copper button and topstitching |
| `designer-leather-wallet-tan.jpg` | Slim tan full-grain leather bifold wallet with tonal stitching, two cards peeking out, three-quarter view |
| `designer-leather-wallet-black.jpg` | Same wallet in black leather |
| `designer-leather-wallet-detail.jpg` | Macro of the wallet's stitched edge and leather grain |

## Homepage and banners

| File | Size | Prompt |
|---|---|---|
| `uploads/header_images/hero-new-season-essentials.jpg` | 1200 x 1200 | Flat-lay of a sky-blue shirt and a navy T-shirt on a soft blue background, modern minimal composition |
| `uploads/header_images/hero-denim-edit.jpg` | 1200 x 1200 | Light-wash denim jacket on a deep navy background with a warm orange accent light |
| `uploads/header_images/hero-crafted-accessories.jpg` | 1200 x 1200 | Tan and black leather wallets on a warm cream background, gift-box styling |
| `uploads/category/mens-clothing.jpg` | 1000 x 1000 | Folded men's shirt and T-shirt stack, light blue background |
| `uploads/category/womens-clothing.jpg` | 1000 x 1000 | Women's denim jacket on a hanger, soft pink background |
| `uploads/category/accessories.jpg` | 1000 x 1000 | Leather wallets and cards, warm cream background |
| `uploads/banner/banner-mens-clothing.jpg`, `banner-womens-clothing.jpg`, `banner-accessories.jpg`, `banner-online-store.jpg` | 1920 x 300 | Wide banner: dark navy left two-thirds (space for the title), the category's products on the right |
| `public/assets/designer/themes/default/images/promo-banner.jpg` | 1400 x 700 | Denim jacket and white T-shirt on the right, deep navy background, empty left half for text |
| `.../images/why-choose-us.jpg` | 1000 x 1000 | Navy gift box with orange ribbon, folded shirt and leather wallet beside it |
| `.../images/customcart-inner-banner.jpg` | 1920 x 520 | Wide navy studio background, folded clothing on the right third |
| `.../images/login-register.jpg` | 900 x 1100 | Portrait: shirt and wallet on a navy background |
| `.../images/mission-vision-image.jpg` | 1000 x 1000 | T-shirt and denim jacket arranged on a light background |

## People and partners

| File | Size | Prompt |
|---|---|---|
| `uploads/team/omar-haddad.jpg`, `lina-farouk.jpg`, `yusuf-rahman.jpg` | 600 x 700 | Professional head-and-shoulders portrait of a (Founder / Head of Merchandising / Customer Experience Lead), smart casual, plain soft background |
| `uploads/testimonial/nora-alqahtani.jpg`, `faisal-mansour.jpg`, `sara-haddad.jpg` | 400 x 400 | Friendly customer portrait, natural light, plain background |
| `uploads/team/swiftline-couriers.jpg`, `silkroute-payments.jpg`, `atlas-cotton-mills.jpg` | 720 x 400 | Keep the rendered wordmarks (fictional brands); replace only with real partners' logos |

## News, gallery, certificates and brochures

| File | Size | Prompt |
|---|---|---|
| `uploads/news/spring-summer-2026-collection.jpg` | 1200 x 800 | Spring/summer clothing editorial flat-lay, fresh light colours |
| `uploads/news/how-to-care-for-denim.jpg` | 1200 x 800 | Denim jacket being hung to dry, bright laundry setting |
| `uploads/news/free-delivery-over-sar-250.jpg` | 1200 x 800 | Branded navy delivery box on a doorstep |
| `uploads/gallery/*.jpg` (4) | 1200 x 900 | Essentials collection; denim details; gift-ready packaging; accessories flat lay |
| `uploads/certificates/*.jpg` | 900 x 1200 | Keep the rendered certificate and brochure covers, or design real ones |
| `uploads/brochures/*.pdf` | A4 | Rendered lookbook and size & care guide; replace with final PDFs when available |

Generator used for the current illustrations: kept outside the project (scratchpad
`gen/gen_art.py` + `gen/render.py`, headless Chrome + PHP GD); not needed at runtime.
