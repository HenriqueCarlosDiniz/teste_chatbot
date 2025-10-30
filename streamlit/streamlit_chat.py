import streamlit as st
import requests
import uuid
import threading
import time
import pysher
import json
import logging
from queue import Queue

# Configuração de logging para depuração
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')

st.set_page_config(layout="wide")

# --- Constantes de Configuração ---
CHATBOT_API_URL = "http://nginx/api/chat"
REVERB_APP_KEY = "eu5sgudkyypngdki4iyl"
REVERB_HOST = "reverb"
REVERB_PORT = 8080

# --- Filas no Session State para persistência ---
if "status_queue" not in st.session_state:
    st.session_state.status_queue = Queue()
if "message_queue" not in st.session_state:
    st.session_state.message_queue = Queue()

status_queue = st.session_state.status_queue
message_queue = st.session_state.message_queue

# --- Estado da Sessão ---
if "session_id" not in st.session_state:
    st.session_state.session_id = str(uuid.uuid4())
if "messages" not in st.session_state:
    st.session_state.messages = []
if "pusher_client" not in st.session_state:
    st.session_state.pusher_client = None
if "pusher_connection_status" not in st.session_state:
    st.session_state.pusher_connection_status = "Desconectado"
if "subscribed_channel" not in st.session_state:
    st.session_state.subscribed_channel = None

# --- Funções do Pusher/Reverb ---
def on_message(message):
    logging.critical(f"!!!!!!!!!!!!!! MENSAGEM RECEBIDA VIA WEBSOCKET !!!!!!!!!!!!!!: {message}")
    message_queue.put(message)

def connect_handler(data):
    logging.info(f"Conexão estabelecida com sucesso: {data}")
    status_queue.put("Conectado")

def connect_pusher():
    if st.session_state.get("pusher_client") and st.session_state.pusher_client.connection.state == "connected":
        return

    st.session_state.pusher_connection_status = "A Conectar..."
    logging.info("Tentando conectar ao servidor Reverb...")
    try:
        pusher = pysher.Pusher(
            key=REVERB_APP_KEY,
            custom_host=REVERB_HOST,
            port=REVERB_PORT,
            secure=False,
            log_level=logging.INFO
        )
        pusher.connection.bind('pusher:connection_established', connect_handler)
        
        connect_thread = threading.Thread(target=pusher.connect)
        connect_thread.daemon = True
        connect_thread.start()
        
        st.session_state.pusher_client = pusher
    except Exception as e:
        logging.error(f"Não foi possível conectar ao servidor de WebSocket (Reverb): {e}")
        st.session_state.pusher_connection_status = "Falha na Conexão"
        st.error(f"Falha ao conectar com o servidor de chat em tempo real: {e}")

# --- Inicialização ---
if st.session_state.pusher_client is None:
    connect_pusher()

# Espera pela atualização do status da conexão (elimina condição de corrida)
max_wait_time = 5  # segundos
start_time = time.time()
while time.time() - start_time < max_wait_time and st.session_state.pusher_connection_status != "Conectado":
    if not status_queue.empty():
        status = status_queue.get()
        if st.session_state.pusher_connection_status != status:
            logging.info(f"Atualizando status da conexão para: {status}")
            st.session_state.pusher_connection_status = status
    else:
        time.sleep(0.1)

if st.session_state.pusher_connection_status != "Conectado":
    logging.warning("Timeout aguardando status de conexão. Tentará novamente na próxima execução.")

# Log para verificar o estado ANTES da tentativa de subscrição
logging.info(f"Verificando condição para subscrição: Status='{st.session_state.pusher_connection_status}', Canal Subscrito='{st.session_state.subscribed_channel}'")
logging.info(f"THREAD PRINCIPAL: Antes do antes de se inscrever no canal: {st.session_state}")

# Inscreve-se no canal se a conexão estiver estabelecida
if st.session_state.pusher_connection_status == "Conectado" and st.session_state.subscribed_channel is None:
    logging.info(f"THREAD PRINCIPAL: Antes de se inscrever no canal: {st.session_state}")
    try:
        channel_name = f"chat.{st.session_state.session_id}"
        logging.info(f"THREAD PRINCIPAL: Inscrevendo-se no canal: {channel_name}")
        channel = st.session_state.pusher_client.subscribe(channel_name)
        channel.bind('message.sent', on_message)
        st.session_state.subscribed_channel = channel_name
        logging.info(f"THREAD PRINCIPAL: Inscrição no canal '{channel_name}' concluída.")
        # Não chama st.rerun() aqui para permitir que o script continue e renderize a UI
    except Exception as e:
        logging.error(f"THREAD PRINCIPAL: Erro ao se inscrever no canal: {e}")
        st.session_state.pusher_connection_status = "Erro na Inscrição"

# --- Interface Gráfica (UI) ---
st.title("Interface de Chat - Pés Sem Dor")

with st.sidebar:
    st.header("Configuração")
    st.text_input("Telefone (simulação WhatsApp)", key="user_phone", placeholder="Ex: 11999998888")
    st.text_input("ID do Usuário (Sessão)", value=st.session_state.session_id, disabled=True)
    
    status_color = "green" if st.session_state.pusher_connection_status == "Conectado" else "orange" if st.session_state.pusher_connection_status == "A Conectar..." else "red"
    st.markdown(f"**Status do Chat:** <span style='color:{status_color};'>●</span> {st.session_state.pusher_connection_status}", unsafe_allow_html=True)

    #if st.button("Limpar e Reiniciar Conversa"):
     #   for key in list(st.session_state.keys()):
      #      del st.session_state[key]
       # st.session_state.status_queue = Queue()
        #st.session_state.message_queue = Queue()
        #st.rerun()

st.header("Chat")

# Exibe o histórico de mensagens
for message in st.session_state.messages:
    with st.chat_message(message["role"]):
        st.markdown(message["content"])

# Processa mensagens da fila
rerun_needed = False
if not message_queue.empty():
    logging.info(f"PROCESSANDO {message_queue.qsize()} MENSAGEM(NS) DA FILA.")
    while not message_queue.empty():
        incoming_message_str = message_queue.get()
        try:
            incoming_message = json.loads(incoming_message_str)
            st.session_state.messages.append({"role": "assistant", "content": incoming_message['responseMessage']})
            rerun_needed = True
        except json.JSONDecodeError as e:
            logging.error(f"Não foi possível decodificar a mensagem recebida como JSON: {e} - Mensagem: {incoming_message_str}")
        except KeyError:
            logging.error(f"A chave 'responseMessage' não foi encontrada na mensagem: {incoming_message_str}")

if rerun_needed:
    st.rerun()

# Captura a nova mensagem do usuário
if prompt := st.chat_input("Digite sua mensagem..."):
    st.session_state.messages.append({"role": "user", "content": prompt})
    
    try:
        payload = {
            "message": prompt,
            "user_id": st.session_state.session_id,
            "phone_number": st.session_state.get("user_phone", "").strip()
        }
        response = requests.post(CHATBOT_API_URL, json=payload, timeout=15)
        response.raise_for_status()
    except requests.exceptions.RequestException as e:
        st.error(f"Erro ao enviar mensagem para a API: {e}")
    
    st.rerun()

# Força a re-execução do script para manter a aplicação "viva" e a verificar as filas.
time.sleep(1)
st.rerun()