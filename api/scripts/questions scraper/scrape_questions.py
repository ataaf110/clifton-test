from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from time import sleep
from datetime import datetime
from pathlib import Path
import json
import random 

questions = []

options = Options()
options.add_argument("--start-maximized")

driver = webdriver.Chrome(options=options)

driver.get('https://esanj.ir/clifton-strengths-test')

driver.find_element(By.CLASS_NAME, 'exam-start-btn').click()

sleep(2)

driver.find_element(By.ID, 'free-card').click()

sleep(2)

driver.find_element(By.ID, 'select2-age-container').click()
age_element = driver.find_elements(By.CSS_SELECTOR, '#select2-age-results .select2-results__option')[20]
print('age selected:\t' + age_element.text)
age_element.click()

sleep(1)

driver.find_element(By.ID, 'select2-gender-container').click()
gender_element = driver.find_elements(By.CSS_SELECTOR, '#select2-gender-results .select2-results__option')[0]
print('gender selected:\t' + gender_element.text)
gender_element.click()

sleep(1)

start_button = driver.find_element(By.CSS_SELECTOR, 'button.dv-start-form-button')
start_button.find_element(By.XPATH, '..').click()

sleep(2)
print('test started')
driver.find_element(By.ID, 'nextQuestion').click()

c = 1

try:
    while True:
        if driver.find_elements(By.ID, 'viewInterpretation'):
            print('test finished')
            break
        
        options = driver.find_elements(By.CSS_SELECTOR, '.box-and-text:not(#answer-3)')
        selected_option = random.choice(options)
        print('question number ' + str(c) + ' option selected:\t' + selected_option.find_element(By.CSS_SELECTOR, 'input.answer').get_attribute('value'))
        selected_option.find_element(By.CSS_SELECTOR, 'label.radio').click()

        questions.append(
            [
                driver.find_element(By.ID, 'title-1').text,
                driver.find_element(By.ID, 'title-2').text

            ]
        )

        sleep(1)

        print('question scraped:\t' + questions[-1][0] + ' ' + questions[-1][1])

        driver.find_element(By.ID, 'nextQuestion').click()

        sleep(1)

        c += 1

except Exception as e:
    print(e)

sleep(5)

driver.quit()

timestamp = datetime.now().strftime('%Y%m%d-%H%M%S')
results_directory = Path(__file__).parent / 'questions'
results_directory.mkdir(exist_ok=True)
result_file = results_directory / f'results-{timestamp}.json'
with open(result_file, 'w', encoding='utf-8') as f:
    json.dump(questions, f, ensure_ascii=False, indent=2)