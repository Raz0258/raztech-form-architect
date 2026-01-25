/**
 * Analytics Dashboard - Chart.js Integration
 *
 * @package    SmartFormsAI
 * @subpackage SmartFormsAI/admin/js
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Submissions Over Time Chart (Line Chart)
		if ($('#submissionsChart').length && typeof rt_faAnalytics !== 'undefined') {
			const submissionsCtx = $('#submissionsChart')[0].getContext('2d');
			new Chart(submissionsCtx, {
				type: 'line',
				data: {
					labels: rt_faAnalytics.submissionsData.labels,
					datasets: [{
						label: 'Submissions',
						data: rt_faAnalytics.submissionsData.values,
						borderColor: '#2271b1',
						backgroundColor: 'rgba(34, 113, 177, 0.1)',
						tension: 0.4,
						fill: true,
						borderWidth: 2,
						pointRadius: 4,
						pointBackgroundColor: '#2271b1',
						pointBorderColor: '#fff',
						pointBorderWidth: 2,
						pointHoverRadius: 6
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							padding: 12,
							titleColor: '#fff',
							bodyColor: '#fff',
							borderColor: '#2271b1',
							borderWidth: 1
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								precision: 0,
								color: '#666'
							},
							grid: {
								color: 'rgba(0, 0, 0, 0.05)'
							}
						},
						x: {
							ticks: {
								color: '#666'
							},
							grid: {
								display: false
							}
						}
					}
				}
			});
		}

		// Lead Score Distribution Chart (Doughnut Chart)
		if ($('#leadScoreChart').length && typeof rt_faAnalytics !== 'undefined') {
			const leadScoreCtx = $('#leadScoreChart')[0].getContext('2d');
			new Chart(leadScoreCtx, {
				type: 'doughnut',
				data: {
					labels: ['High (80-100)', 'Medium (50-79)', 'Low (0-49)'],
					datasets: [{
						data: rt_faAnalytics.leadScoreData.values,
						backgroundColor: [
							'#46b450', // Green for high
							'#ffb900', // Yellow for medium
							'#dc3232'  // Red for low
						],
						borderWidth: 2,
						borderColor: '#fff',
						hoverOffset: 10
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
							labels: {
								padding: 15,
								font: {
									size: 13
								},
								color: '#666'
							}
						},
						tooltip: {
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							padding: 12,
							titleColor: '#fff',
							bodyColor: '#fff',
							callbacks: {
								label: function(context) {
									const label = context.label || '';
									const value = context.parsed || 0;
									const total = context.dataset.data.reduce((a, b) => a + b, 0);
									const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
									return label + ': ' + value + ' (' + percentage + '%)';
								}
							}
						}
					}
				}
			});
		}

		// Spam vs Legitimate Chart (Bar Chart)
		if ($('#spamChart').length && typeof rt_faAnalytics !== 'undefined') {
			const spamCtx = $('#spamChart')[0].getContext('2d');
			new Chart(spamCtx, {
				type: 'bar',
				data: {
					labels: ['Legitimate', 'Spam', 'Suspicious'],
					datasets: [{
						label: 'Count',
						data: rt_faAnalytics.spamData.values,
						backgroundColor: [
							'#46b450', // Green for legitimate
							'#dc3232', // Red for spam
							'#ffb900'  // Yellow for suspicious
						],
						borderWidth: 0,
						borderRadius: 6
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							padding: 12,
							titleColor: '#fff',
							bodyColor: '#fff',
							borderColor: '#46b450',
							borderWidth: 1
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								precision: 0,
								color: '#666'
							},
							grid: {
								color: 'rgba(0, 0, 0, 0.05)'
							}
						},
						x: {
							ticks: {
								color: '#666'
							},
							grid: {
								display: false
							}
						}
					}
				}
			});
		}

		// Handle empty data states
		if (typeof rt_faAnalytics !== 'undefined') {
			// Check if submissions chart has no data
			const totalSubmissions = rt_faAnalytics.submissionsData.values.reduce((a, b) => a + b, 0);
			if (totalSubmissions === 0 && $('#submissionsChart').length) {
				$('#submissionsChart').parent().html('<p style="text-align: center; padding: 60px 0; color: #666;">' +
					'No submission data available yet. Data will appear here once forms receive submissions.' +
					'</p>');
			}

			// Check if lead score chart has no data
			const totalLeadScores = rt_faAnalytics.leadScoreData.values.reduce((a, b) => a + b, 0);
			if (totalLeadScores === 0 && $('#leadScoreChart').length) {
				$('#leadScoreChart').parent().html('<p style="text-align: center; padding: 60px 0; color: #666;">' +
					'No lead score data available yet.' +
					'</p>');
			}

			// Check if spam chart has no data
			const totalSpam = rt_faAnalytics.spamData.values.reduce((a, b) => a + b, 0);
			if (totalSpam === 0 && $('#spamChart').length) {
				$('#spamChart').parent().html('<p style="text-align: center; padding: 60px 0; color: #666;">' +
					'No spam detection data available yet.' +
					'</p>');
			}
		}
	});

})(jQuery);
