# Continy Module Vite Scripts

Re-usable Vite based scripts module

## 설정법

```
composer require shoplic-kr/continy-module-vite-scripts
```

플러그인, 테마 기본으로 필요한 JS/TS 기본 파일은 아래 명령과 같이 워드프레스 외부 임의의 장소에 샘플 프로젝트를 생성한 후,
얻어지는 파일들을 추출하여 적절히 사용합니다. 이렇게 하는 이유는 Vite 번들러가 버전업하면서 조금씩 템플릿 설정이 변경될 수 있기 때문입니다.
가급적 최신 버전의, 최신 설정의 템플릿 파일을 사용하세요.

```
pnpm create vite vite-sample --template react-swc-ts
```

아래와 같은 파일 목록들을 가져올 수 있습니다.

- eslint.config.js
- package.json
- tsconfig.json
- tsconfig.*.json
- vite.config.ts

PHP 스크립트의 루트 디렉토리는 `inc`로 할 것을 권장합니다.
그리고 JS/TS 스크립트의 루트 디렉토리는 `src`로 할 것을 권장합니다.

플러그인의 설정 방법은 `sample/sample-plugin`을 참조로 하여 설정하세요.

`sample/sample-plugin/src` 디렉토리의 `refresh.js`와 `vite-env.d.ts`를 복사해 JS/TS 스크립트의 루트 디렉토리로 복사하세요.


## Scripts 모듈 사용법

### 초기화
Scripts 모듈은 'init' 액션에 초기화 시킵니다.

```php
$scripts = new ShoplicKr\Continy\ViteScripts\Modules\Scripts(
    // 설정
    [
        'basePath'  => plugin_dir_path(__FILE__),
        'baseUrl'   => plugin_dir_url(__FILE__),
        'isDevMode' => 'production' !== wp_get_environment_type(),
        'prefix'    => '[적절한접두어]'
    ],
);
```

### 진입점

`sample/sample-plugin`의 `vite.config.ts` 파일에는 src 디렉토리의 루트 .tsx 파일을 빌드 대상으로 합니다.
`build.rollupOptions.input'의 값을 적절히 튜닝하여 빌드할 진입점을 수정할 수 있습니다.


### 스크립트 삽입하기

필요한 부분에 Scripts 모듈을 불러와 아래처럼 작업합니다.

```php
/** @var ShoplicKr\Continy\ViteScripts\Modules\Scripts $scripts **/ 

$scripts
    ->enqueueViteScript('entry.tsx')
    ->localize('varName', ['var1' => 'foo', 'var2' => 'bar']);
```

`entry.tsx`는 `src/` 디렉토리 아래 진입점의 파일의 상대 경로입니다.
`varName`으로 `entry.tsx`에 필요한 데이터를 전달할 수 있습니다.


### 마은트 포인트

적절히 컴포넌트를 마운트할 부분을 출력합니다.

```php
<div id="root"></div>
```


### 마운팅하기

```tsx
import 'vite/modulepreload-polyfill'
import {StrictMode} from 'react'
import {createRoot} from 'react-dom/client'

const root = document.getElementById('root')
if (root) {
    createRoot(root).render(
        <StrictMode>
            {/* 리액트 콤토넌트 */}
        </StrictMode>
    )
}
```
