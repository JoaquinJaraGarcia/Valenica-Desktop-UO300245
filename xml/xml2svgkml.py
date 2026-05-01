import xml.etree.ElementTree as ET
import os

class Svg(object):
    # Clase para generar el documento SVG
    def __init__(self, width=1200, height=500):
       self.raiz = ET.Element('svg', attrib={'xmlns': "http://www.w3.org/2000/svg", 'version': "1.1",
                       'width': str(width), 'height': str(height)})

    def addRect(self, x, y, width, height, fill):
        ET.SubElement(self.raiz, 'rect', x=str(x), y=str(y), width=str(width), height=str(height), fill=fill)

    def addLine(self, x1, y1, x2, y2, stroke, strokeWidth):
        ET.SubElement(self.raiz, 'line', x1=str(x1), y1=str(y1), x2=str(x2), y2=str(y2), stroke=stroke, **{'stroke-width': str(strokeWidth)})

    def addPolyline(self, points, stroke, strokeWidth, fill):
        ET.SubElement(self.raiz, 'polyline', points=points, stroke=stroke, fill=fill, **{'stroke-width': str(strokeWidth)})

    def addText(self, texto, x, y, fontFamily, fontSize, textAnchor="start", transform=None):
        atributos = {'x': str(x), 'y': str(y), 'font-family': fontFamily, 'font-size': str(fontSize), 'text-anchor': textAnchor}
        if transform:
            atributos['transform'] = transform
        el = ET.SubElement(self.raiz, 'text', **atributos)
        el.text = texto
    def escribir(self, nombreArchivoSVG):
        arbol = ET.ElementTree(self.raiz)
        ET.indent(arbol)
        arbol.write(nombreArchivoSVG, encoding='utf-8', xml_declaration=True)

def generar_kml(nombre_ruta, coordenadas, archivo_salida):
    # Genera el archivo KML de planimetria
    kml_content = f"""<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<Document>
    <Name>{nombre_ruta}</Name>
    <Placemark>
        <LineString>
            <extrude>1</extrude>
            <tessellate>1</tessellate>
            <coordinates>\n{coordenadas}\n            </coordinates>
        </LineString>
    </Placemark>
</Document>
</kml>"""
    with open(archivo_salida, 'w', encoding='utf-8') as f:
        f.write(kml_content)

def generar_svg_mejorado(nombre_ruta, altitudes, distancias,nombres_hitos, archivo_salida):
    # Genera el SVG con ejes y relleno
    W, H, P = 1200, 500, 60 

    dist_acumulada = []
    suma = 0
    for d in distancias:
        suma += d
        dist_acumulada.append(suma)

    total_dist = dist_acumulada[-1] if dist_acumulada else 1.0
    min_alt = min(altitudes)
    max_alt = max(altitudes)
    rango_alt = max_alt - min_alt if (max_alt - min_alt) != 0 else 1.0

    ancho_dibujo = W - 2 * P
    alto_dibujo = H - 2 * P
    puntos_svg = []
    s = Svg()
    for i, (d, alt) in enumerate(zip(dist_acumulada, altitudes)):
        x = P + (d / total_dist) * ancho_dibujo
        y = P + ((max_alt - alt) / rango_alt) * alto_dibujo
        puntos_svg.append((x, y))
        
       

    perfil_str = " ".join(f"{x:.2f},{y:.2f}" for x, y in puntos_svg)
    cierre_str = f" {puntos_svg[-1][0]:.2f},{H-P:.2f} {puntos_svg[0][0]:.2f},{H-P:.2f}"
    poly_cerrada = perfil_str + cierre_str

   
    s.addRect(0, 0, W, H, "white")
    s.addLine(P, H-P, W-P, H-P, "black", 2) 
    s.addLine(P, P, P, H-P, "black", 2) 
    
    s.addPolyline(poly_cerrada, "steelblue", 2, "#cfe8ff")
    s.addPolyline(perfil_str, "navy", 2, "none")
    
    for i, (x, y) in enumerate(puntos_svg):
        alt = altitudes[i]
        
        # El círculo rojo
        ET.SubElement(s.raiz, 'circle', cx=str(x), cy=str(y), r="4", fill="red")
        
        # Cadena HORIZONTAL (Nombre del hito por encima del punto)
        # Lo añadimos directamente con SubElement para garantizar el atributo fill="black"
        ET.SubElement(s.raiz, 'text', x=str(x), y=str(y - 12), fill="black", 
                      **{"font-family": "Verdana", "font-size": "12", "text-anchor": "middle"}).text = nombres_hitos[i]
        
        # Cadena VERTICAL (Altitud rotada por debajo del punto)
        ET.SubElement(s.raiz, 'text', x=str(x + 5), y=str(y + 15), fill="black", 
                      transform=f"rotate(-90 {x+5} {y+15})", 
                      **{"font-family": "Verdana", "font-size": "10", "text-anchor": "end"}).text = f"{alt} m"
    s.addText(nombre_ruta, W/2, 30, "Verdana", 20, "middle")
    s.addText(f"Max: {max_alt:.1f} m", P+10, P-10, "Verdana", 14)
    s.addText(f"Min: {min_alt:.1f} m", P+10, H-P+30, "Verdana", 14)
    s.addText(f"Distancia Total: {total_dist:.2f} m", W/2, H-10, "Verdana", 14, "middle")

    s.escribir(archivo_salida)

def procesar_xml(archivo_xml):
    # Lee el XML y ejecuta las conversiones
    tree = ET.parse(archivo_xml)
    root = tree.getroot()
    
    for idx, ruta in enumerate(root.findall('ruta')):
        nombre_ruta = ruta.find('nombre').text
        hitos = ruta.find('hitos').findall('hito')
        
        coords_kml = ""
        altitudes = []
        distancias = []
        nombres_hitos = [] # NUEVA LISTA
        for hito in hitos:
            nombre_hito = hito.find('nombreHito').text # CAPTURAMOS EL NOMBRE
            coords_hito = hito.find('coordenadasHito')
            lon = coords_hito.find('longitud').text
            lat = coords_hito.find('latitud').text
            alt = coords_hito.find('altitud').text
            distancia_el = hito.find('distancia')
            valor_dist = float(distancia_el.text)
            unidades = distancia_el.get('unidades', 'metros')

# Convertir todo a metros
            if unidades.lower() in ('kilometros', 'km'):
             dist_metros = valor_dist * 1000.0
            else:
             dist_metros = valor_dist
            
            coords_kml += f"{lon},{lat},{alt}\n"
            altitudes.append(float(alt))
            distancias.append(dist_metros)
            nombres_hitos.append(nombre_hito) # LO GUARDAMOS
        num_ruta = idx + 1
        generar_kml(nombre_ruta, coords_kml.strip(), f"ruta{num_ruta}_planimetria.kml")
        generar_svg_mejorado(nombre_ruta, altitudes, distancias,nombres_hitos, f"ruta{num_ruta}_altimetria.svg")

if __name__ == "__main__":
    # Obtener la ruta del directorio donde está el script
    script_dir = os.path.dirname(os.path.abspath(__file__))
    archivo_xml = os.path.join(script_dir, 'rutas.xml')
    procesar_xml(archivo_xml)