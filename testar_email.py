"""Testa o envio de email Gmail diretamente."""
import smtplib
from email.mime.text import MIMEText

remetente = 'rickgouveia157@gmail.com'
senha     = 'wyfy dywn czaq outy'.replace(' ', '')  # remove espaços
destino   = 'rickgouveia157@gmail.com'

msg = MIMEText('Teste de email do sistema Logi-Prime. Se chegou, o Gmail SMTP está funcionando!', 'plain', 'utf-8')
msg['From']    = remetente
msg['To']      = destino
msg['Subject'] = 'Teste Logi-Prime — Gmail SMTP'

try:
    with smtplib.SMTP_SSL('smtp.gmail.com', 465) as smtp:
        smtp.login(remetente, senha)
        smtp.send_message(msg)
    print('✅ Email enviado com sucesso!')
except Exception as e:
    print(f'❌ Erro: {e}')
