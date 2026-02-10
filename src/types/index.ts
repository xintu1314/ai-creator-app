// 模型类型
export interface Model {
  id: string;
  name: string;
  description: string;
  icon: string;
  isNew?: boolean;
  tags: string[];
}

// 模板类型
export interface Template {
  id: string;
  title: string;
  image: string;
  model: string;
  type: 'image' | 'video';
  category?: string;
}

// 创作类型
export type CreationType = 'image' | 'video' | 'digital' | 'motion';

// 图片比例
export type AspectRatio = '1:1' | '2:3' | '3:2' | '3:4' | '4:3' | '9:16' | '16:9' | '9:21' | '21:9';

// 生成参数
export interface GenerationParams {
  model: string;
  aspectRatio: AspectRatio;
  count: number;
  quality: '2k' | '4k';
  mode: 'single' | 'multiple';
}
