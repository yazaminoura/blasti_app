import os
import sys
import time
import subprocess
from io import BytesIO
from PIL import Image, ImageDraw, ImageFont
import imageio_ffmpeg
from playwright.sync_api import sync_playwright

OUTPUT_FILE = r"C:\Users\Ismail\Downloads\blasti\Blasti_app\blasti_app_presentation.mp4"
BASE_URL = "http://127.0.0.1:8000"
WIDTH = 1920
HEIGHT = 1080
FPS = 25

FONT_PATH = r"C:\Windows\Fonts\segoeui.ttf"
FONT_BOLD_PATH = r"C:\Windows\Fonts\segoeuib.ttf"

try:
    font_title = ImageFont.truetype(FONT_BOLD_PATH, 28)
    font_desc = ImageFont.truetype(FONT_PATH, 20)
    font_badge = ImageFont.truetype(FONT_BOLD_PATH, 16)
    font_brand = ImageFont.truetype(FONT_BOLD_PATH, 24)
except Exception:
    font_title = ImageFont.load_default()
    font_desc = ImageFont.load_default()
    font_badge = ImageFont.load_default()
    font_brand = ImageFont.load_default()

def draw_overlay(img, chapter_num, total_chapters, chapter_title, chapter_desc, progress_ratio):
    # Ensure image is in RGB and exact 1920x1080
    if img.size != (WIDTH, HEIGHT):
        img = img.resize((WIDTH, HEIGHT), Image.Resampling.LANCZOS)
    if img.mode != 'RGB':
        img = img.convert('RGB')
    
    # Create overlay
    overlay = Image.new('RGBA', (WIDTH, HEIGHT), (0, 0, 0, 0))
    draw = ImageDraw.Draw(overlay)
    
    # Lower third bar height: 110px
    bar_y = HEIGHT - 110
    draw.rectangle([0, bar_y, WIDTH, HEIGHT], fill=(10, 18, 35, 235))
    draw.rectangle([0, bar_y, WIDTH, bar_y + 3], fill=(11, 79, 196, 255)) # Brand Blue accent line
    
    # Chapter badge
    badge_text = f"ÉTAPE {chapter_num}/{total_chapters}"
    badge_w = 110
    draw.rounded_rectangle([30, bar_y + 18, 30 + badge_w, bar_y + 44], radius=6, fill=(11, 79, 196, 255))
    draw.text((42, bar_y + 22), badge_text, fill=(255, 255, 255), font=font_badge)
    
    # Title
    draw.text((155, bar_y + 18), chapter_title, fill=(255, 255, 255), font=font_title)
    # Description
    draw.text((30, bar_y + 55), chapter_desc, fill=(203, 213, 225), font=font_desc)
    
    # Brand logo text on right
    brand_text = "BLASTI · بلاصتي"
    draw.text((WIDTH - 240, bar_y + 20), brand_text, fill=(255, 255, 255), font=font_brand)
    draw.text((WIDTH - 240, bar_y + 55), "Vue Réelle de l'Application", fill=(148, 163, 184), font=font_desc)
    
    # Progress bar at very bottom
    prog_w = int(WIDTH * progress_ratio)
    draw.rectangle([0, HEIGHT - 6, WIDTH, HEIGHT], fill=(30, 41, 59, 255))
    if prog_w > 0:
        draw.rectangle([0, HEIGHT - 6, prog_w, HEIGHT], fill=(59, 130, 246, 255))
    
    # Composite
    base = img.convert('RGBA')
    composed = Image.alpha_composite(base, overlay)
    return composed.convert('RGB')

def create_title_card(title, subtitle, duration_sec, ffmpeg_proc, chapter_num, total_chapters):
    img = Image.new('RGB', (WIDTH, HEIGHT), (10, 18, 38))
    draw = ImageDraw.Draw(img)
    
    # Background subtle styling
    draw.rectangle([0, 0, WIDTH, 8], fill=(11, 79, 196))
    
    # Big logo icon
    try:
        font_big = ImageFont.truetype(FONT_BOLD_PATH, 64)
        font_sub = ImageFont.truetype(FONT_PATH, 32)
        font_small = ImageFont.truetype(FONT_PATH, 22)
    except Exception:
        font_big = font_title
        font_sub = font_desc
        font_small = font_desc

    draw.text((WIDTH // 2 - 250, HEIGHT // 2 - 130), "BLASTI  بلاصتي", fill=(255, 255, 255), font=font_big)
    draw.rectangle([WIDTH // 2 - 250, HEIGHT // 2 - 40, WIDTH // 2 + 250, HEIGHT // 2 - 36], fill=(11, 79, 196))
    draw.text((WIDTH // 2 - 340, HEIGHT // 2 - 15), title, fill=(226, 232, 240), font=font_sub)
    draw.text((WIDTH // 2 - 280, HEIGHT // 2 + 45), subtitle, fill=(148, 163, 184), font=font_small)
    
    frames_count = int(duration_sec * FPS)
    raw_data = img.tobytes()
    for _ in range(frames_count):
        ffmpeg_proc.stdin.write(raw_data)

def main():
    print(f"Starting video recording from {BASE_URL}...")
    ffmpeg_exe = imageio_ffmpeg.get_ffmpeg_exe()
    
    # Launch ffmpeg process
    cmd = [
        ffmpeg_exe,
        "-y",
        "-f", "rawvideo",
        "-vcodec", "rawvideo",
        "-s", f"{WIDTH}x{HEIGHT}",
        "-pix_fmt", "rgb24",
        "-r", str(FPS),
        "-i", "-",
        "-c:v", "libx264",
        "-pix_fmt", "yuv420p",
        "-preset", "faster",
        "-crf", "20",
        OUTPUT_FILE
    ]
    
    ffmpeg_proc = subprocess.Popen(cmd, stdin=subprocess.PIPE)
    total_steps = 7

    print("Launching Microsoft Edge via Playwright...")
    with sync_playwright() as p:
        browser = p.chromium.launch(channel="msedge", headless=True)
        context = browser.new_context(viewport={"width": WIDTH, "height": HEIGHT})
        page = context.new_page()

        # Step 0: Intro Title Card (3 seconds)
        print("Rendering Intro Card...")
        create_title_card(
            "Plateforme Interurbaine de Réservation de Bus",
            "Démonstration Guidée des Vues et Fonctionnalités Réelles",
            3.0,
            ffmpeg_proc,
            0, total_steps
        )

        # Step 1: Accueil & Moteur de Recherche
        print("Capturing Step 1: Accueil & Recherche...")
        page.goto(f"{BASE_URL}/", wait_until="networkidle")
        time.sleep(1.0)
        
        # Capture scroll down smoothly
        for step_i in range(120): # 120 frames = ~4.8s
            scroll_y = int(min(650, step_i * 6))
            page.evaluate(f"window.scrollTo(0, {scroll_y})")
            buf = page.screenshot(type="jpeg", quality=90)
            img = Image.open(BytesIO(buf))
            frame = draw_overlay(
                img, 1, total_steps,
                "Page d'Accueil & Moteur de Recherche",
                "Recherche de trajet Casablanca → Fès, sélection de date et consultation des prochains départs",
                1 / total_steps
            )
            ffmpeg_proc.stdin.write(frame.tobytes())

        # Step 2: Liste des Voyages & Filtres
        print("Capturing Step 2: Liste des Voyages...")
        page.goto(f"{BASE_URL}/voyages/list", wait_until="networkidle")
        time.sleep(1.0)
        for step_i in range(120): # ~4.8s
            scroll_y = int(min(500, step_i * 5))
            page.evaluate(f"window.scrollTo(0, {scroll_y})")
            buf = page.screenshot(type="jpeg", quality=90)
            img = Image.open(BytesIO(buf))
            frame = draw_overlay(
                img, 2, total_steps,
                "Liste des Voyages & Filtres Avancés",
                "Comparateur des départs : compagnies, horaires, prix calculés et places restantes",
                2 / total_steps
            )
            ffmpeg_proc.stdin.write(frame.tobytes())

        # Step 3: Plan Visuel des Sièges
        print("Capturing Step 3: Plan des Sièges...")
        page.goto(f"{BASE_URL}/client/reservations/60", wait_until="networkidle")
        time.sleep(1.2)
        # Scroll down to seat map
        for step_i in range(150): # ~6.0s
            scroll_y = int(min(750, step_i * 6))
            page.evaluate(f"window.scrollTo(0, {scroll_y})")
            buf = page.screenshot(type="jpeg", quality=90)
            img = Image.open(BytesIO(buf))
            frame = draw_overlay(
                img, 3, total_steps,
                "Plan Réel d'Occupation des Sièges (2 + 2)",
                "Disposition exacte de l'autocar : sièges libres, occupés et sélection directe par le passager",
                3 / total_steps
            )
            ffmpeg_proc.stdin.write(frame.tobytes())

        # Step 4: Login Admin & Dashboard
        print("Capturing Step 4: Administration & KPIs...")
        page.goto(f"{BASE_URL}/login", wait_until="networkidle")
        page.fill('input[name="email"]', 'admin@blasti.ma')
        page.fill('input[name="password"]', 'password')
        page.click('button[type="submit"]')
        page.wait_for_load_state("networkidle")
        time.sleep(1.2)
        
        # On admin dashboard
        for step_i in range(150): # ~6.0s
            scroll_y = int(min(600, step_i * 5))
            page.evaluate(f"window.scrollTo(0, {scroll_y})")
            buf = page.screenshot(type="jpeg", quality=90)
            img = Image.open(BytesIO(buf))
            frame = draw_overlay(
                img, 4, total_steps,
                "Tableau de Bord Administrateur & Chiffre d'Affaires",
                "Indicateurs clés en direct : CA mensuel, réservations, flotte d'autocars et destinations phares",
                4 / total_steps
            )
            ffmpeg_proc.stdin.write(frame.tobytes())

        # Step 5: Espace Chauffeur Opérationnel
        print("Capturing Step 5: Espace Chauffeur...")
        page.goto(f"{BASE_URL}/admin/chauffeur/voyage/60", wait_until="networkidle")
        time.sleep(1.2)
        for step_i in range(130): # ~5.2s
            scroll_y = int(min(450, step_i * 4))
            page.evaluate(f"window.scrollTo(0, {scroll_y})")
            buf = page.screenshot(type="jpeg", quality=90)
            img = Image.open(BytesIO(buf))
            frame = draw_overlay(
                img, 5, total_steps,
                "Espace Chauffeur & Feuille de Route",
                "Suivi opérationnel anonymisé : passagers à bord, montées/descentes par arrêt et signalement de retard",
                5 / total_steps
            )
            ffmpeg_proc.stdin.write(frame.tobytes())

        # Step 6: Scanner de Billets en Direct
        print("Capturing Step 6: Scanner en Direct...")
        page.goto(f"{BASE_URL}/reservation/admin/scanner", wait_until="networkidle")
        time.sleep(1.2)
        for step_i in range(130): # ~5.2s
            buf = page.screenshot(type="jpeg", quality=90)
            img = Image.open(BytesIO(buf))
            frame = draw_overlay(
                img, 6, total_steps,
                "Contrôle & Scanner de Billets à l'Embarquement",
                "Vérification instantanée par caméra, validation du bon autocar et encaissement en caisse",
                6 / total_steps
            )
            ffmpeg_proc.stdin.write(frame.tobytes())

        # Step 7: Guichet de Vente en Gare
        print("Capturing Step 7: Guichet...")
        page.goto(f"{BASE_URL}/admin/guichet", wait_until="networkidle")
        time.sleep(1.2)
        for step_i in range(130): # ~5.2s
            scroll_y = int(min(300, step_i * 3))
            page.evaluate(f"window.scrollTo(0, {scroll_y})")
            buf = page.screenshot(type="jpeg", quality=90)
            img = Image.open(BytesIO(buf))
            frame = draw_overlay(
                img, 7, total_steps,
                "Vente au Guichet & Impression Thermique",
                "Émission rapide de billets au comptoir en gare avec impression instantanée (ticket 80mm)",
                7 / total_steps
            )
            ffmpeg_proc.stdin.write(frame.tobytes())

        # Outro Card (3 seconds)
        print("Rendering Outro Card...")
        create_title_card(
            "Blasti · Solution Complète Prête au Déploiement",
            "Laravel 12 · Blade · Bootstrap 5 · CMI 3D Secure",
            3.0,
            ffmpeg_proc,
            7, total_steps
        )

        browser.close()

    ffmpeg_proc.stdin.close()
    ffmpeg_proc.wait()
    print(f"SUCCESS: Video generated at {OUTPUT_FILE}")

if __name__ == "__main__":
    main()
