
(function($) {
	'use strict';

	var WPLG = {

		RequestTypeColor: {
			page: '#68f',
			'404': '#c8c',
			ajax: '#cc0',
			rest: '#c80',
			cron: '#999',
			login: '#2c2',
			system: '#d0f',
		},

		Boundaries: {},
		DataSet: {},
		TicksY: {},


		Init: function() {
			this.InitTimePickers();
			this.SetupCanvas();
		},


		InitTimePickers: function () {
			$.datetimepicker.setLocale('en');
			var $inputFrom = $('#wploadgraph_from');
			var $inputTo = $('#wploadgraph_to');
			$inputFrom.datetimepicker({
				format: 'U',
				step: 10,
				inline: true,
				lang: 'en',
				showTimezone: true,
				LocalTimezone: false,
				onChangeDateTime: WPLG.OnChangeDateTime,
			});
			$inputTo.datetimepicker({
				format: 'U',
				step: 10,
				inline: true,
				lang: 'en',
				showTimezone: true,
				onChangeDateTime: WPLG.OnChangeDateTime,
			});
		},


		OnChangeDateTime: function (newDateTime, $input) {
			$input.val(Math.round(newDateTime.valueOf() / 1000));
		},


		SetupCanvas: function() {
			var ctx = document.getElementById('wploadgraphChart');

			function packDataset() {
				var dataset = [];
				var chartLine = 0;
				var pointsCount= 0;
				// prepare data
				$.each(window.WpLoadGraphData.trace, function(sess, rows) {
					rows.forEach(row => {
						row.forEach(item => {
							var color = WPLG.RequestTypeColor[item.type] || '#888';
							if (item.error) {
								color = '#f00';
							}
							dataset.push({
								label: sess + "\n" + item.type + "\n" + item.path + "\n" + item.error + "\n" + item.mem + "\n" + item.db,
								backgroundColor: color,
								borderColor: color,
								fill: false,
								borderWidth : window.WpLoadGraphData.pointSize,
								pointStyle: 'circle',
								pointRadius: 2,
								pointBorderWidth: window.WpLoadGraphData.pointSize / 2 - 2,
								data: [
									{x: new Date(item.ts1 * 1000), y: chartLine},
									{x: new Date(item.ts2 * 1000), y: chartLine}
								]
							});
							WPLG.Boundaries.minX = Math.min(WPLG.Boundaries.minX || item.ts1, item.ts1);
							WPLG.Boundaries.maxX = Math.max(WPLG.Boundaries.maxX || item.ts2, item.ts2);
							WPLG.Boundaries.minY = Math.min(WPLG.Boundaries.minY || chartLine, chartLine);
							WPLG.Boundaries.maxY = Math.max(WPLG.Boundaries.maxY || chartLine, chartLine);
							pointsCount++;
							var sessParts = sess.split('(');
							WPLG.TicksY[chartLine] = sessParts.length === 1 ? sess.substring(0,7)+'..' : sessParts[0];
						});
						chartLine--;
					});
				});
				console.log('pointsCount', dataset.length, 'pointSize', window.WpLoadGraphData.pointSize);
				//console.log('trace',window.WpLoadGraphData.trace);
				//console.log('dataset', dataset);
				WPLG.DataSet = dataset;
			}


			function tooltip(context) {
				var tooltipEl = document.getElementById('wploadgraph-chartjs-tooltip');
				if (!tooltipEl) {
					tooltipEl = document.createElement('div');
					tooltipEl.id = 'wploadgraph-chartjs-tooltip';
					tooltipEl.innerHTML = '<table></table>';
					document.body.appendChild(tooltipEl);
				}
				const tooltipModel = context.tooltip;
				if (tooltipModel.opacity === 0) {
					tooltipEl.style.opacity = 0;
					return;
				}
				tooltipEl.classList.remove('above', 'below', 'no-transform');
				tooltipEl.classList.add(tooltipModel.yAlign ? tooltipModel.yAlign : 'no-transform');
				if (tooltipModel.body) {
					//console.log('tooltipModel', tooltipModel);
					//console.log('context', context);
					var parts =  tooltipModel.body[0].lines[0].split(':').slice(0,-1).join(':').split("\n");
					var color = WPLG.RequestTypeColor[parts[1]] || '#888';
					//var timestamp = context.tooltip.dataPoints[0].dataset.data[0].x;
					var timestamp = context.tooltip.dataPoints[0].raw.x;
					var formattedTime = timestamp.toLocaleString('en-CA', {hour12:false});
					let lines = [
						'<tr><td>Time: <span>'+formattedTime+'</span></td></tr>',
						'<tr><td>Session: <span>'+parts[0]+'</span></td></tr>',
						'<tr><td>Type: <span style="color:'+color+';font-weight:bold">'+parts[1]+'</span></td></tr>',
						'<tr><td>Memory: <span>'+parts[4]+' Mb</span></td></tr>',
						'<tr><td>DB queries: <span>'+parts[5]+'</span></td></tr>',
						'<tr><td>Path: <span>'+parts[2]+'</span></td></tr>',
					];
					if (parts[3] === '1') {
						lines.push('<tr><td style="color:red">Fatal Error</td></tr>');
					}
					tooltipEl.querySelector('table').innerHTML = '<tbody>'+lines.join('')+'</tbody>';
				}

				var position = context.chart.canvas.getBoundingClientRect();
				//var bodyFont = Chart.helpers.toFont(tooltipModel.options.bodyFont);
				tooltipEl.style.opacity = 1;
				tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
				tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + 'px';
				tooltipEl.style.padding = tooltipModel.padding + 'px ' + tooltipModel.padding + 'px';
			}

			packDataset();
			var deltaX = WPLG.Boundaries.minX ? WPLG.Boundaries.maxX - WPLG.Boundaries.minX : 3600;
			var today = Math.floor(new Date().valueOf()/86400000)*86400;
			var scaleLimits = {
				min: ((WPLG.Boundaries.minX || today) - deltaX/10) * 1000,
				max: ((WPLG.Boundaries.maxX || today + 86400) + deltaX/10) * 1000,
			};

			var zoomOptions = {
				zoom: {
					wheel: {
						enabled: true,
					},
					pinch: {
						enabled: true,
					},
					mode: 'x',

				},
				pan: {
					enabled: true,
					mode: 'x',
				},
				limits: {
					minRange: 1000000000,
					x: {min:scaleLimits.min, max: scaleLimits.max},
					//y: {min:0, max: 3},
				}
			};

			var scales = {
				x: {
					position: 'bottom',
					type: 'time',
					ticks: {
						autoSkip: true,
						autoSkipPadding: 50,
						maxRotation: 0
					},
					time: {
						displayFormats: {
							hour: 'HH:mm',
							minute: 'HH:mm',
							second: 'HH:mm:ss',
							millisecond: "HH:mm:ss.SSS",
						}
					},
					min: new Date(scaleLimits.min),
					max: new Date(scaleLimits.max),
				},
				y: {
					position: 'left',
					ticks: {
						//display: false,
						callback: (val, index, ticks) => index === 0 || index === ticks.length - 1 ? null : WPLG.TicksY[val],
						autoSkip: false,
					},
					grid: {
						display: true,
						borderColor: '#cfc',
						color: 'rgba( 180, 180, 180, 0.2)',
					},
					title: {
						display: false,
						text: (ctx) => ctx.scale.axis + ' axis',
					},
					min: Math.min(WPLG.Boundaries.minY || -6, -4) - 1,
					max: (WPLG.Boundaries.maxY || 0) + 2,
				},
			};

			var config = {
				type: 'line',
				data: {
					datasets: WPLG.DataSet,
				},
				options: {
					interaction: {
						intersect: false,
						mode: 'nearest',
					},
					responsive: true,
					maintainAspectRatio: false,
					scales: scales,
					plugins: {
						zoom: zoomOptions,
						legend: {
							display: false
						},
						/*title: {
							display: true,
							position: 'bottom',
							text: (ctx) => 'Zoom: ' + zoomStatus() + ', Pan: ' + panStatus()
						},*/
						tooltip: {
							enabled: false,
							external: tooltip,
						},
					},
					onClick(e) {
						console.log(e.type);
					}
				},
			};


			new Chart(ctx, config);
		}
	}


	// on DOM ready
	$(function() {
		if ($('.wploadgraph-dashboard').length === 0) {
			return;
		}
		WPLG.Init();
		window.WpLoadGraph = WPLG;
	});

})(jQuery);


