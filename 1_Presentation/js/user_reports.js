// Bar Chart: Savings per Category
const ctxBar = document.getElementById('categoryBarChart').getContext('2d');
const categoryBarChart = new Chart(ctxBar, {
  type: 'bar',
  data: {
    labels: ['Emergency Funds', 'Travel', 'Education'], // Categories for the x-axis
    datasets: [{
      label: '₱ Saved',
      data: [10000, 10000, 10000], // Replace with dynamic values if needed
      backgroundColor: '#fa8fbc', // Bar color
      borderColor: '#24336e', // Border color of bars
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    layout: {
      padding: {
        left: 20,   // Left padding for chart area
        right: 20,  // Right padding for chart area
        top: 20,    // Top padding for chart area
        bottom: 5  // Bottom padding for chart area
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          color: '#24336e' // Color for y-axis ticks
        }
      },
      x: {
        ticks: {
          color: '#24336e' // Color for x-axis ticks
        }
      }
    },
    plugins: {
      legend: {
        labels: {
          color: '#24336e' // Color for legend text
        }
      }
    }
  }
});

// Doughnut Chart: Goal Completion
const ctxDoughnut = document.getElementById('goalDoughnutChart').getContext('2d');
const goalDoughnutChart = new Chart(ctxDoughnut, {
  type: 'doughnut',
  data: {
    labels: ['Completed', 'Remaining'],
    datasets: [{
      label: 'Goals',
      data: [10, 10], // 10 out of 20 goals completed
      backgroundColor: ['#fa8fbc', '#ffd1dc'],
      borderColor: '#fff3f8',
      borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    layout: {
      padding: {
        left: 20,   // Left padding for chart area
        right: 20,  // Right padding for chart area
        top: 20,    // Top padding for chart area
        bottom: 20  // Bottom padding for chart area
      }
    },
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          color: '#24336e'
        }
      }
    }
  }
});
