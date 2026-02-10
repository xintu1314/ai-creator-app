import { useState } from 'react';
import { ChevronDown, Image, Video } from 'lucide-react';
import { cn } from '@/lib/utils';
import ModelSelectDialog from './ModelSelectDialog';
import type { Model } from '@/types';

const imageModels: Model[] = [
  { id: 'banana-pro', name: 'banana pro', description: '高性能图片生成模型', icon: 'banana', tags: ['图片生成'] },
];

const videoModels: Model[] = [
  { id: 'kling', name: '可灵', description: '高质量视频生成模型', icon: 'kling', tags: ['视频生成', '首尾帧'] },
  { id: 'sora2', name: 'sora2', description: '先进的视频生成模型', icon: 'sora', tags: ['视频生成', '首尾帧'] },
];

const categories = ['室内', '景观', '建筑', '电商', '人物', '动物', '自然'];

export default function Publish() {
  const [contentType, setContentType] = useState<'image' | 'video'>('image');
  const [selectedModel, setSelectedModel] = useState<string>('');
  const [selectedCategory, setSelectedCategory] = useState<string>('');
  const [modelDialogOpen, setModelDialogOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');

  const availableModels = contentType === 'image' ? imageModels : videoModels;
  const selectedModelData = availableModels.find(m => m.id === selectedModel);

  const handlePublish = () => {
    // 发布逻辑
    console.log('发布:', { contentType, selectedModel, selectedCategory, title, content });
  };

  return (
    <div className="max-w-[1200px] mx-auto p-6">
      <h1 className="text-2xl font-semibold text-[#1A1A1A] mb-6">发布模板</h1>
      
      <div className="bg-white rounded-lg p-6 border border-[#E5E5E5]">
        {/* Content Type Selection (Image/Video) */}
        <div className="mb-6">
          <label className="block text-sm font-medium text-[#1A1A1A] mb-3">类型</label>
          <div className="flex gap-4">
            <button
              onClick={() => {
                setContentType('image');
                setSelectedModel(''); // 重置模型选择
              }}
              className={cn(
                "px-4 py-2 rounded-lg transition-colors flex items-center gap-2",
                contentType === 'image'
                  ? "bg-[#3B82F6] text-white"
                  : "bg-[#F5F5F5] text-[#666666] hover:bg-[#E5E5E5]"
              )}
            >
              <Image className="w-4 h-4" />
              图片
            </button>
            <button
              onClick={() => {
                setContentType('video');
                setSelectedModel(''); // 重置模型选择
              }}
              className={cn(
                "px-4 py-2 rounded-lg transition-colors flex items-center gap-2",
                contentType === 'video'
                  ? "bg-[#3B82F6] text-white"
                  : "bg-[#F5F5F5] text-[#666666] hover:bg-[#E5E5E5]"
              )}
            >
              <Video className="w-4 h-4" />
              视频
            </button>
          </div>
        </div>

        {/* Model Selection */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-[#1A1A1A] mb-2">选择模型</label>
          <button
            onClick={() => setModelDialogOpen(true)}
            className="w-full px-4 py-2 border border-[#E5E5E5] rounded-lg flex items-center justify-between hover:border-[#3B82F6] transition-colors bg-white"
          >
            <span className={selectedModel ? "text-[#1A1A1A]" : "text-[#999999]"}>
              {selectedModelData ? selectedModelData.name : '请选择模型'}
            </span>
            <ChevronDown className="w-4 h-4 text-[#666666]" />
          </button>
        </div>

        {/* Category Selection */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-[#1A1A1A] mb-2">分类</label>
          <div className="flex flex-wrap gap-2">
            {categories.map((category) => (
              <button
                key={category}
                onClick={() => setSelectedCategory(category)}
                className={cn(
                  "px-4 py-1.5 text-sm font-medium rounded-lg transition-colors",
                  selectedCategory === category
                    ? "bg-[#3B82F6] text-white"
                    : "bg-[#F5F5F5] text-[#666666] hover:bg-[#E5E5E5]"
                )}
              >
                {category}
              </button>
            ))}
          </div>
        </div>

        {/* Title */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-[#1A1A1A] mb-2">模板标题</label>
          <input 
            type="text" 
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            className="w-full px-4 py-2 border border-[#E5E5E5] rounded-lg focus:outline-none focus:border-[#3B82F6]"
            placeholder="输入模板标题（提示词）"
          />
        </div>

        {/* Content */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-[#1A1A1A] mb-2">模板内容（提示词）</label>
          <textarea 
            value={content}
            onChange={(e) => setContent(e.target.value)}
            className="w-full px-4 py-2 border border-[#E5E5E5] rounded-lg focus:outline-none focus:border-[#3B82F6] min-h-[200px]"
            placeholder="输入模板内容（提示词）"
          />
        </div>

        {/* Publish Button */}
        <button 
          onClick={handlePublish}
          disabled={!selectedModel || !selectedCategory || !title || !content}
          className="px-6 py-2 bg-[#3B82F6] hover:bg-[#2563EB] disabled:bg-[#E5E5E5] disabled:text-[#999999] text-white rounded-lg transition-colors"
        >
          发布
        </button>
      </div>

      {/* Model Select Dialog */}
      <ModelSelectDialog
        open={modelDialogOpen}
        onOpenChange={setModelDialogOpen}
        models={availableModels}
        selectedModel={selectedModel}
        onSelectModel={setSelectedModel}
        title="选择模型"
      />
    </div>
  );
}
