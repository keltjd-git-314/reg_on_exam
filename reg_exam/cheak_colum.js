function validateForm() {
    const form = document.forms['Exam'];

    // Валидация ФИО студента
    const lastName = form['last_name'].value.trim();
    const firstName = form['first_name'].value.trim();

    if (lastName === '' || firstName === '') {
        alert('Заполните фамилию и имя студента');
        if (lastName === '') form['last_name'].focus();
        else form['first_name'].focus();
        return false;
    }

    // Валидация ФИО преподавателя
    const teacherLastName = form['teacher_last_name'].value.trim();
    const teacherFirstName = form['teacher_first_name'].value.trim();

    if (teacherLastName === '' || teacherFirstName === '') {
        alert('Заполните фамилию и имя преподавателя');
        if (teacherLastName === '') form['teacher_last_name'].focus();
        else form['teacher_first_name'].focus();
        return false;
    }

    // Остальная валидация остается
    const subject = form['subject'].value;
    const day = form['day'].value;
    const month = form['month'].value;
    const time = form['time'].value;

    if (subject === '') {
        alert('Выберите предмет для сдачи');
        form['subject'].focus();
        return false;
    }

    if (day === '' || month === '') {
        alert('Выберите дату сдачи');
        if (day === '') form['day'].focus();
        else form['month'].focus();
        return false;
    }

    if (time === '') {
        alert('Выберите время сдачи');
        form['time'].focus();
        return false;
    }

    return true;
}