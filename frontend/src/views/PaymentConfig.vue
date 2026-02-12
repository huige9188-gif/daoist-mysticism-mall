<template>
  <div class="payment-config">
    <el-card>
      <template #header>
        <span>支付配置</span>
      </template>

      <el-tabs v-model="activeTab">
        <el-tab-pane label="支付宝" name="alipay">
          <el-form :model="alipayForm" label-width="120px" class="config-form">
            <el-form-item label="App ID">
              <el-input v-model="alipayForm.app_id" />
            </el-form-item>
            <el-form-item label="私钥">
              <el-input v-model="alipayForm.private_key" type="textarea" :rows="3" />
            </el-form-item>
            <el-form-item label="公钥">
              <el-input v-model="alipayForm.public_key" type="textarea" :rows="3" />
            </el-form-item>
            <el-form-item label="回调URL">
              <el-input v-model="alipayForm.callback_url" />
            </el-form-item>
            <el-form-item label="状态">
              <el-switch v-model="alipayForm.status" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="handleSave('alipay')">保存配置</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane label="微信支付" name="wechat">
          <el-form :model="wechatForm" label-width="120px" class="config-form">
            <el-form-item label="App ID">
              <el-input v-model="wechatForm.app_id" />
            </el-form-item>
            <el-form-item label="商户号">
              <el-input v-model="wechatForm.mch_id" />
            </el-form-item>
            <el-form-item label="API密钥">
              <el-input v-model="wechatForm.api_key" type="password" />
            </el-form-item>
            <el-form-item label="回调URL">
              <el-input v-model="wechatForm.callback_url" />
            </el-form-item>
            <el-form-item label="状态">
              <el-switch v-model="wechatForm.status" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="handleSave('wechat')">保存配置</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <el-tab-pane label="PayPal" name="paypal">
          <el-form :model="paypalForm" label-width="120px" class="config-form">
            <el-form-item label="Client ID">
              <el-input v-model="paypalForm.client_id" />
            </el-form-item>
            <el-form-item label="Secret">
              <el-input v-model="paypalForm.secret" type="password" />
            </el-form-item>
            <el-form-item label="回调URL">
              <el-input v-model="paypalForm.callback_url" />
            </el-form-item>
            <el-form-item label="状态">
              <el-switch v-model="paypalForm.status" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="handleSave('paypal')">保存配置</el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getPaymentConfigs, savePaymentConfig } from '@/api/payment'

export default {
  name: 'PaymentConfig',
  setup() {
    const activeTab = ref('alipay')

    const alipayForm = reactive({
      gateway: 'alipay',
      app_id: '',
      private_key: '',
      public_key: '',
      callback_url: '',
      status: 1
    })

    const wechatForm = reactive({
      gateway: 'wechat',
      app_id: '',
      mch_id: '',
      api_key: '',
      callback_url: '',
      status: 1
    })

    const paypalForm = reactive({
      gateway: 'paypal',
      client_id: '',
      secret: '',
      callback_url: '',
      status: 1
    })

    const loadConfigs = async () => {
      try {
        const response = await getPaymentConfigs()
        const configs = response.data

        configs.forEach(config => {
          const configData = typeof config.config === 'string' ? JSON.parse(config.config) : config.config
          
          if (config.gateway === 'alipay') {
            Object.assign(alipayForm, configData, { status: config.status })
          } else if (config.gateway === 'wechat') {
            Object.assign(wechatForm, configData, { status: config.status })
          } else if (config.gateway === 'paypal') {
            Object.assign(paypalForm, configData, { status: config.status })
          }
        })
      } catch (error) {
        ElMessage.error('加载支付配置失败')
      }
    }

    const handleSave = async (gateway) => {
      try {
        let form
        if (gateway === 'alipay') form = alipayForm
        else if (gateway === 'wechat') form = wechatForm
        else if (gateway === 'paypal') form = paypalForm

        await savePaymentConfig({
          gateway,
          config: form,
          status: form.status
        })
        ElMessage.success('保存成功')
      } catch (error) {
        ElMessage.error('保存失败')
      }
    }

    onMounted(() => {
      loadConfigs()
    })

    return {
      activeTab,
      alipayForm,
      wechatForm,
      paypalForm,
      handleSave
    }
  }
}
</script>

<style scoped>
.config-form {
  max-width: 600px;
  margin-top: 20px;
}
</style>
