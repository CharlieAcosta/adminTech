const fs = require('fs');
const path = './01-views/layout/footer_layout.php'; // ← adaptá si tu ruta cambia

try {
    let html = fs.readFileSync(path, 'utf8');

    const now = new Date();
    const yy = String(now.getFullYear()).slice(-2);
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');
    const hh = String(now.getHours()).padStart(2, '0');
    const min = String(now.getMinutes()).padStart(2, '0');

    const version = `${yy}${mm}${dd}-${hh}${min}`;

    const regex = /<!--FECHA-AUTO--><span>.*?<\/span><!--\/FECHA-AUTO-->/;
    const nuevoSpan = `<!--FECHA-AUTO--><span>${version}</span><!--/FECHA-AUTO-->`;

    if (regex.test(html)) {
        const actualizado = html.replace(regex, nuevoSpan);
        fs.writeFileSync(path, actualizado);
        console.log(`[Actualizado] layout.html → ${version}`);
    } else {
        console.warn("⚠ No se encontró el marcador <!--FECHA-AUTO--><span>...</span><!--/FECHA-AUTO-->");
    }
} catch (error) {
    console.error("❌ Error al actualizar layout:", error.message);
}
