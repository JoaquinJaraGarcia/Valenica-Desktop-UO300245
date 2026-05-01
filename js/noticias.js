class Noticias {
    constructor(busqueda = "") {
        // Término de búsqueda que se enviará en la llamada a la API
        this.busqueda = busqueda;
        this.apiKey = "GyPsyA8HaSzvnWOFOgcWhvtL5Lip3mt6FBEoI4A4";
        this.url = "https://api.thenewsapi.com/v1/news/all";
        this.contenedor = $("<section>");
    }

    buscar() {
        const peticion = `${this.url}?api_token=${this.apiKey}&search=${encodeURIComponent(this.busqueda)}&language=es`;

        return fetch(peticion)
            .then(respuesta => {
                if (!respuesta.ok) {
                    throw new Error("Error en la respuesta de la API");
                }
                return respuesta.json();   // <- devuelve JSON
            });
    }

    iniciarNoticias() {
        this.contenedor.appendTo("main");
        this.buscar()
            .then(json => {
                const lista = this.procesarInformacion(json);
                this.mostrarNoticias(lista);
            })
            .catch(err => console.error(err));

    }

    procesarInformacion(json) {
        // Comprobación básica de formato
        if (!json || !json.data) {
            console.error("JSON no válido o sin datos.");
            return [];
        }
        return json.data.map(noticia => ({
            titulo: noticia.title,
            entradilla: noticia.description,
            enlace: noticia.url,
            fuente: noticia.source
        }));

    }


    mostrarNoticias(noticias) {
        const $h2 = $("<h2>").text("Noticias relacionadas con " + this.busqueda);
        this.contenedor.append($h2); // Agrega el título solo una vez, antes de la primera noticia
        noticias.forEach(noticia => {
            const $articulo = $("<article>");
            const $titulo = $("<h3>").text(noticia.titulo);
            const $entradilla = $("<p>").text(noticia.entradilla);
            const $enlace = $("<a>").attr("href", noticia.enlace).attr("target", "_blank").text("Leer más");
            const $fuente = $("<small>").text(`Fuente: ${noticia.fuente}`);

            $articulo.append($titulo, $entradilla, $enlace, $fuente);
            this.contenedor.append($articulo);
        });
    }
}


