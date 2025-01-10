jQuery(document).ready(function ($) {
    const weatherDisplay = $('#weatherwidgetnl-weather-display');

	function escapeHtml(text) {
	    return $('<div>').text(text).html();
	}

    if (weatherDisplay.length) {
        const title = weatherDisplay.data('title');
        const location = weatherDisplay.data('location');
        const iso = weatherDisplay.data('iso');
        const language = weatherDisplay.data('language');
        const icon = weatherDisplay.data('icon');
        const unit = weatherDisplay.data('unit');
        const days = weatherDisplay.data('days');

		$.ajax({
		    url: weatherwidgetnlWeatherWidgetData.apiUrl,
		    method: 'GET',
		    data: {
		        language: language,
		        title: title,
		        location: location,
		        iso: iso,
		        unit: unit,
		        days: days
		    },
		    timeout: 10000 // 10 seconden
		})
		    .done(function (data) {
				if (data && data.temp) {
					let iconUrl = `${weatherwidgetnlWeatherWidgetData.imgBaseUrl}${escapeHtml(data.icon)}.png`;
					let forecastHtml = '';
					if (days !== 0 && data.forecast && Array.isArray(data.forecast)) {
					    forecastHtml = `<div class="weatherwidgetnl-weather-forecast days-${days}">`;
					    data.forecast.forEach(day => {
					    	let dayIconUrl = `${weatherwidgetnlWeatherWidgetData.imgBaseUrl}${escapeHtml(day.icon)}.png`;
					        forecastHtml += `
					            <div class="forecast-item">
					                <div class="forecast-day">${escapeHtml(day.day)}</div>
					                <div class="forecast-date">${escapeHtml(day.date)}</div>
					                <div class="forecast-weather">
					                    <img src="${dayIconUrl}" width="50" height="50" alt="${escapeHtml(day.icon)}" class="forecast-icon">
					                    <div class="forecast-temp">${escapeHtml(day.tempmax)}${escapeHtml(data.tempUnit)}</div>
					                </div>
					            </div>
					        `;
					    });
					    forecastHtml += '</div>';
					}

				    weatherDisplay.html(`
				    	<div class="weatherwidgetnl-weather-title">${escapeHtml(data.title)}</div>
					    <div class="weatherwidgetnl-weather-content">
					    	<div class="weatherwidgetnl-weather-grid">
					    		<img src="${iconUrl}" width="64" height="64" alt="${escapeHtml(data.icon)}">
					        	<div class="temperature">${escapeHtml(data.temp)}${escapeHtml(data.tempUnit)}</div>
					        </div>
					        <div class="weatherwidgetnl-weather-icons">
					        	<div class="weatherwidgetnl-weather-item">
					        		${data.precipIcon} ${escapeHtml(data.precip)} ${escapeHtml(data.precipUnit)}
					        	</div>
					        	<div class="weatherwidgetnl-weather-item">
					        		${data.precipprobIcon} ${escapeHtml(data.precipprob)}%
					        	</div>
					        	<div class="weatherwidgetnl-weather-item">
					        		${data.windspeedIcon} ${escapeHtml(data.windspeed)} ${escapeHtml(data.windspeedUnit)}
					        	</div>
					        	<div class="weatherwidgetnl-weather-item">
					        		${data.humidityIcon} ${escapeHtml(data.humidity)}%
					        	</div>
					        </div>
					        ${forecastHtml}
					    </div>
				    `);
				} else {
				    console.warn('Incomplete data received:', data);
				    weatherDisplay.html('<p>Error: Incomplete data received.</p>');
				}
            })
            .fail(function (xhr, status, error) {
                console.error('API Error:', error, 'Status:', status, 'Response:', xhr.responseText);
                weatherDisplay.html('<p>Error fetching weather data. Please try again later.</p>');
            });
    }
});
