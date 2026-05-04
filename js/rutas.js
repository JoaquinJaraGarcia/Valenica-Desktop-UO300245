class GestorRutas {
    constructor() {
        // Seleccionamos el primer input dentro del nav
        this.contenedorRutas = document.querySelector("main > section");
        this.cargarXMLInicial();
    }

   cargarXMLInicial() {
        $.ajax({
            url: 'xml/rutas.xml',
            dataType: 'text',
            success: (textoXML) => {
                let xmlSeguro = textoXML.replace(/<!DOCTYPE[^>]*>/g, '');
                let datos = $.parseXML(xmlSeguro);
                this.procesarXML(datos);
            },
            error: () => console.error("Fallo crítico al cargar rutas.xml")
        });
    }
    procesarXML(datos) {
        let self = this;

        $("ruta", datos).each(function (index) {
            let numRuta = index + 1;
           let articulo = $("<article>");
            
            articulo.append($("<h3>").text(`Ruta ${numRuta}: ${$("nombre", this).first().text()}`));
            articulo.append($("<p>").html(`<strong>Tipo:</strong> ${$("tipo", this).text()}`));
            articulo.append($("<p>").html(`<strong>Transporte:</strong> ${$("transporte", this).text()}`));
            
            if ($("fechaInicio", this).length) {
                articulo.append($("<p>").html(`<strong>Fecha Inicio:</strong> ${$("fechaInicio", this).text()} a las ${$("horaInicio", this).text()}`));
            }
            
            articulo.append($("<p>").html(`<strong>Duración:</strong> ${$("duracion", this).text()}`));
            articulo.append($("<p>").html(`<strong>Agencia:</strong> ${$("agencia", this).text()}`));
            articulo.append($("<p>").html(`<strong>Descripción:</strong> ${$("descripcion", this).first().text()}`));
            articulo.append($("<p>").html(`<strong>Recomendada para:</strong> ${$("personasAdecuadas", this).text()}`));
            articulo.append($("<p>").html(`<strong>Lugar de inicio:</strong> ${$("lugarInicio", this).text()} (${$("direccionInicio", this).text()})`));
            
            let coordInicio = $("coordenadasInicio", this);
            articulo.append($("<p>").html(`<strong>Coordenadas Inicio:</strong> Lon: ${$("longitud", coordInicio).text()}, Lat: ${$("latitud", coordInicio).text()}, Alt: ${$("altitud", coordInicio).text()}m`));
            
            articulo.append($("<p>").html(`<strong>Puntuación:</strong> ${$("recomendacion", this).text()}/10`));

            let refs = $("referencias > referencia", this);
            if (refs.length > 0) {
                articulo.append($("<h4>").text("Referencias"));
                let listaRefs = $("<ul>");
                refs.each(function () {
                    let link = $(this).text();
                    listaRefs.append($("<li>").append($("<a>").attr("href", link).text(link)));
                });
                articulo.append(listaRefs);
            }

            // --- SECCIÓN PLANIMETRÍA (KML) ---
           let archivoKML = $("planimetria", this).text();
            let archivoSVG = $("altimetria", this).text();

            articulo.append($("<h4>").text("Planimetría"));
            let figuraMapa = $("<figure>");
            articulo.append(figuraMapa);
            self.cargarArchivoSecundario(archivoKML, figuraMapa[0], 'kml');

            articulo.append($("<h4>").text("Altimetría"));
            let figuraSVG = $("<figure>");
            articulo.append(figuraSVG);
            self.cargarArchivoSecundario(archivoSVG, figuraSVG[0], 'svg');

            $(self.contenedorRutas).append(articulo);

            let hitos = $("hitos > hito", this);
if (hitos.length > 0) {
    articulo.append($("<h4>").text("Hitos de la Ruta"));
    
    hitos.each(function (idx) {
        let seccionHito = $("<section>");
        let nombreHito = $("nombreHito", this).text();
        
        seccionHito.append($("<h5>").text(`${idx + 1}. ${nombreHito}`));
        seccionHito.append($("<p>").text($("descripcionHito", this).text()));
        
        let coordHito = $("coordenadasHito", this);
        seccionHito.append($("<p>").html(`<em>Lon: ${$("longitud", coordHito).text()}, Lat: ${$("latitud", coordHito).text()}, Alt: ${$("altitud", coordHito).text()}m</em>`));
        seccionHito.append($("<p>").html(`<strong>Distancia desde inicio:</strong> ${$("distancia", this).text()} ${$("distancia", this).attr("unidades")}`));
        
        // --- CÓDIGO NUEVO PARA CARGAR IMÁGENES ---
        let fotos = $("galeriaFotografias > fotografia", this);
        if (fotos.length > 0) {
            fotos.each(function () {
                let textoFoto = $(this).text().trim();
                let archivoFoto = "./" + textoFoto.replace(/^(\.\.\/|\/)/, '');
                let figura = $("<figure>");
                let img = $("<img>").attr({
                    src: archivoFoto,
                    alt: `Imagen de ${nombreHito}`
                });
                figura.append(img);
                seccionHito.append(figura);
            });
        }

        // --- CÓDIGO NUEVO PARA CARGAR VÍDEOS ---
        let videos = $("galeriaVideos > video", this);
        if (videos.length > 0) {
            videos.each(function () {
                let textoVideo = $(this).text().trim();
                let archivoVideo = "./" + textoVideo.replace(/^(\.\.\/|\/)/, '');
                let figura = $("<figure>");
                let video = $("<video>").attr({
                    src: archivoVideo,
                    controls: true // Muestra los botones de play/pausa
                });
                figura.append(video);
                seccionHito.append(figura);
            });
        }
        
        articulo.append(seccionHito);
    });
}
        });
    }
    cargarArchivoSecundario(nombreArchivo, nodoDOM, tipo) {
        $.ajax({
            url: "xml/" + nombreArchivo,
            dataType: "text",
            success: (contenido) => {
                if (tipo === 'kml') {
                    this.procesarKML(contenido, nodoDOM);
                } else if (tipo === 'svg') {
                    this.procesarSVG(contenido, nodoDOM);
                }
            },
            error: () => {
                $(nodoDOM).append($("<p>").text(`No se pudo cargar: ${nombreArchivo}`));
            }
        });
    }

    // --- PARSEO Y DIBUJADO DEL KML ---
    procesarKML(contenidoKML, nodoDOM) {
        try {
            const parser = new DOMParser();
            const docKML = parser.parseFromString(contenidoKML, 'application/xml');
            
            let coordenadasRuta = [];
          let nodosCoordenadas = docKML.getElementsByTagNameNS('*', 'coordinates');
            
            // Como plan B por si el navegador es antiguo
            if (nodosCoordenadas.length === 0) {
                nodosCoordenadas = docKML.getElementsByTagName('coordinates');
            }

            // Si sigue sin encontrar nada, te avisamos para no fallar en silencio
            if (nodosCoordenadas.length === 0) {
                alert("ERROR: No se ha encontrado la etiqueta <coordinates> en tu archivo KML.");
                return;
            }

            // Procesamos las coordenadas
            for (let i = 0; i < nodosCoordenadas.length; i++) {
                let texto = nodosCoordenadas[i].textContent.trim().replace(/\s+/g, " ");
                let puntos = texto.split(" ");
                
                puntos.forEach(punto => {
                    let partes = punto.split(",");
                    // Validamos que el punto tenga al menos longitud y latitud
                    if (partes.length >= 2) {
                        let lng = parseFloat(partes[0]);
                        let lat = parseFloat(partes[1]);
                        
                        // Si son números válidos, los guardamos
                        if (!isNaN(lat) && !isNaN(lng)) {
                            coordenadasRuta.push({ lat: lat, lng: lng });
                        }
                    }
                });
            }

            // Si logramos extraer los puntos, dibujamos el mapa
            if (coordenadasRuta.length > 0) {
                
                // Seguro de vida: Forzamos la altura del mapa por si el CSS falla
                nodoDOM.style.height = "400px";
                nodoDOM.style.width = "100%";

                const mapa = new google.maps.Map(nodoDOM, {
                    zoom: 14,
                    center: coordenadasRuta[0],
                    mapTypeId: 'terrain'
                });

                const trazoRuta = new google.maps.Polyline({
                    path: coordenadasRuta,
                    geodesic: true,
                    strokeColor: '#FF0000', // Línea roja
                    strokeOpacity: 1.0,
                    strokeWeight: 4
                });
                
                trazoRuta.setMap(mapa);

                // EXTRA: Ajustar la cámara automáticamente para que se vea toda la ruta
                const limites = new google.maps.LatLngBounds();
                coordenadasRuta.forEach(coord => limites.extend(coord));
                mapa.fitBounds(limites);

            } else {
                alert("El archivo se leyó, pero los números de las coordenadas no son válidos.");
            }
        } catch (error) {
            alert("Error crítico de JavaScript al procesar el archivo KML. Revisa la consola (F12).");
            console.error(error);
        }
    }

    // --- PARSEO E INSERCIÓN DEL SVG ---
    procesarSVG(contenidoSVG, nodoDOM) {
        try {
            const parser = new DOMParser();
            const docSVG = parser.parseFromString(contenidoSVG, 'image/svg+xml');
            const elementoSVG = docSVG.documentElement;
            
            // Limpiamos el figure por si había un SVG anterior y lo añadimos
            nodoDOM.innerHTML = '';
            nodoDOM.appendChild(elementoSVG);
        } catch (error) {
            alert("Error al procesar el archivo SVG.");
        }
    }
}

$(document).ready(function () {
    new GestorRutas();
});