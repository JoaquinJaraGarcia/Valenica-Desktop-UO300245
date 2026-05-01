class Ciudad {
  // Constructor: recibe nombre, país y gentilicio
  constructor(nombre, longitud, latitud) {
    this.nombre = nombre;
    this.coordenadas = { lat: latitud, lon: longitud };
    //this.getMeteorologiaEntrenos();
    this.url = "https://api.open-meteo.com/v1/forecast";
  }



  getMeteorologiaEntrenos() {
    // parámetros para la API Open-Meteo (gratuita, sin API key requerida)
    /* const url = "https://open-meteo.com/en/docs/historical-weather-api?latitude=" + this.coordenadas.lat +
    "&longitude=" + this.coordenadas.lon +
    "&start_date=2025-07-18&end_date=2025-07-20&hourly=temperature_2m,relative_humidity_2m,apparent_temperature,rain,wind_speed_10m,wind_direction_10m&daily=sunrise,sunset&timezone=auto"; */
    const url = "https://api.open-meteo.com/v1/forecast?latitude=" +
      this.coordenadas.lat + "&longitude=" + this.coordenadas.lon
      + "&daily=temperature_2m_max,weather_code,temperature_2m_min,rain_sum,precipitation_hours&timezone=auto";
    return $.ajax({
      url: url,
      method: "GET",
      dataType: "json"
    });
  }

  traducirCodigoClima(codigo) {
    const codigos = {
      0: "Despejado",
      1: "Mayormente despejado",
      2: "Parcialmente nublado",
      3: "Nublado",
      45: "Niebla",
      48: "Niebla con escarcha",
      51: "Llovizna ligera",
      53: "Llovizna moderada",
      55: "Llovizna densa",
      61: "Lluvia leve",
      63: "Lluvia moderada",
      65: "Lluvia fuerte",
      71: "Nieve leve",
      73: "Nieve moderada",
      75: "Nieve fuerte",
      80: "Chubascos leves",
      81: "Chubascos moderados",
      82: "Chubascos violentos",
      95: "Tormenta",
      96: "Tormenta con granizo leve",
      99: "Tormenta con granizo fuerte"
    };
    return codigos[codigo] || "Desconocido";
  }


  formatearFecha(fechaISO) {
    const partes = fechaISO.split('-');
    if (partes.length === 3) {
      return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }
    return fechaISO;
  }
  mostrarEnHTML(selectorHoy, selectorPrevision) {
    const $contenedorHoy = $(selectorHoy);
    const $contenedorPrevision = $(selectorPrevision);
    if ($contenedorHoy.length === 0 || $contenedorPrevision.length === 0) return;

    this.getMeteorologiaEntrenos().done(datos => {
      const dias = this.procesarJSONEntrenos(datos);
      if (!dias) return;

      // Extraer el día actual (índice 0)
      const hoy = dias[0];
      let htmlHoy = `
        <article>
          <h3>El tiempo hoy en ${this.nombre} (${hoy.fecha})</h3>
          <p>Estado del cielo: <strong>${hoy.codigoTiempo}</strong></p>
          <p>Temperaturas: Máxima de ${hoy.tempMax} °C, Mínima de ${hoy.tempMin} °C</p>
          <p>Precipitaciones: ${hoy.lluviaTotal} mm durante ${hoy.horasPrecipitacion} horas</p>
        </article>
      `;
      $contenedorHoy.find("article").remove();
      $contenedorHoy.append(htmlHoy);

      // Extraer los próximos días (del índice 1 al final)
      const prevision = dias.slice(1);
      let htmlPrevision = "";
      prevision.forEach(dia => {
        htmlPrevision += `
          <article>
            <h3>${dia.fecha}</h3>
            <p>${dia.codigoTiempo}</p>
            <p>Máx: ${dia.tempMax} °C | Mín: ${dia.tempMin} °C</p>
          </article>
        `;
      });
      $contenedorPrevision.find("article").remove();
      $contenedorPrevision.append(htmlPrevision);
    })
      .fail((jqXHR, textStatus, errorThrown) => {
        console.log("Fallo en la conexión AJAX: ", textStatus, errorThrown);
      });
  }

  procesarJSONEntrenos(datos) {
    if (!datos || !datos.daily || !datos.daily.time) return null;
    return datos.daily.time.map((fecha, i) => ({
      fecha: this.formatearFecha(fecha),
      tempMax: datos.daily.temperature_2m_max[i],
      tempMin: datos.daily.temperature_2m_min[i],
      codigoTiempo: this.traducirCodigoClima(datos.daily.weather_code[i]),
      lluviaTotal: datos.daily.rain_sum[i],
      horasPrecipitacion: datos.daily.precipitation_hours[i]
    }));
  }
}

