function setProgress(percent, circleId, textId) {
    const circle = document.getElementById(circleId);
    const text = document.getElementById(textId);
    const radius = circle.r.baseVal.value;
    const circumference = 2 * Math.PI * radius;
  
    circle.style.strokeDasharray = `${circumference}`;
    const offset = circumference - (percent / 100) * circumference;
    circle.style.strokeDashoffset = offset;
  
    text.textContent = `${percent}%`;
  }
  
  setProgress(10, 'savingsCircle', 'savingsText');
  setProgress(10, 'goalsCircle', 'goalsText');
  
  function generateCalendar() {
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();
  
    const monthNames = [
      "January", "February", "March", "April", "May", "June", 
      "July", "August", "September", "October", "November", "December"
    ];
  
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();
  
    const calendar = document.getElementById('calendarTable');
    const monthYearTitle = document.getElementById('calendarMonthYear');
    calendar.innerHTML = '';
  
    monthYearTitle.innerText = `${monthNames[currentMonth]} ${currentYear}`;
  
    const weekdays = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
    let headerRow = "<tr>";
    weekdays.forEach(day => {
      headerRow += `<th>${day}</th>`;
    });
    headerRow += "</tr>";
    calendar.innerHTML += headerRow;
  
    let day = 1;
    for (let i = 0; i < 6; i++) {
      let row = "<tr>";
      for (let j = 0; j < 7; j++) {
        if ((i === 0 && j < firstDay) || day > totalDays) {
          row += "<td></td>";
        } else {
          const isToday = day === today.getDate();
          row += `<td class="${isToday ? 'today' : ''}">${day}</td>`;
          day++;
        }
      }
      row += "</tr>";
      calendar.innerHTML += row;
    }
  }
  
  generateCalendar();
  
  const dailyQuotes = [
    "Save money and money will save you.",
    "A penny saved is a penny earned.",
    "Do something today that your future self will thank you for.",
    "Small steps every day lead to big savings.",
    "Don’t go broke trying to look rich."
  ];
  
  function setDailyQuote() {
    const today = new Date();
    const quoteIndex = today.getDate() % dailyQuotes.length;
    const quoteElement = document.getElementById("dailyQuote");
    if (quoteElement) {
      quoteElement.textContent = `"${dailyQuotes[quoteIndex]}"`;
    }
  }
  
  setDailyQuote();
  